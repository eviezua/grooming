<template>
  <div class="hero-masters-wrapper container-wide">
    <groomer-item
        v-for="(g, index) in groomers"
        :key="g.id || index"
        :groomer="g"
        :selected-services-ids="ServiceIds"
        :service-names="serviceNames"
        :prices-map="pricesMap"
        :city-names="cityNames"
        :district-names="districtNames"
        @open-booking="openBookingModal"
    />
  </div>
  <a
      v-if="hasMore && !preview"
      href="#"
      @click.prevent="loadMore"
      class="orange-but d-flex justify-content-center align-items-center text-white text-decoration-none inter-18 mx-auto"
      style="width: 182px; height: 73px;"
  >
    {{ $t('More') }}
  </a>
  <BookingForm
      ref="bookingForm"
      :groomer="selectedGroomer"
      @close="closeBookingModal"
  />
</template>

<script>
import GroomerItem from './GroomerItem.vue'
import { ref, onMounted, watch, computed, nextTick } from 'vue'
import BookingFormVue from "./BookingForm.vue";
import { useApiFetch } from "../useFetchResource";
import { useDebounce } from "../useDebounce";

export default {
  components: {BookingForm: BookingFormVue, GroomerItem },
  props: {
    filters: {
      type: Object,
      default: () => ({})
    },
    preview: { type: Boolean, default: true }
  },
  setup(props) {
    const { fetchData, loading: apiLoading } = useApiFetch();
    const groomers = ref([])
    const currentPage = ref(1)
    const lastPage = ref(1)
    const loading = ref(false)
    const bookingForm = ref(null)
    const selectedGroomer = ref(null)
    const serviceNames = ref({})
    const pricesMap = ref({})
    const cityNames = ref({})
    const districtNames = ref({})

    const mapItems = (json, nameKey) => (json['hydra:member'] || json['member'] || []).forEach(item => {
      if (nameKey === 'city') cityNames.value[item.id] = item.city;
      if (nameKey === 'dist') districtNames.value[item.id] = item.name;
    });

    const ServiceIds = computed(() => {
      const ids = new Set();
      if (props.filters.service) ids.add(Number(props.filters.service));
      if (props.filters.services?.length) {
        props.filters.services.forEach(id => ids.add(Number(id)));
      }
      return [...ids];
    });
    const buildUrl = (page = 1) => {
      let url = `/api/v1/masters?page=${page}`
      const params = new URLSearchParams()
      const allServiceIds = new Set();
      if (props.filters.search && props.filters.search.length >= 3) params.append('search', props.filters.search)
      if (props.filters.city) params.append('id_city.id', props.filters.city)
      if (props.filters.district) params.append('district.id', props.filters.district)
      if (props.filters.breed) params.append('id_pets.id', props.filters.breed)
      if (props.filters.service) allServiceIds.add(props.filters.service)
      if (props.filters.services?.length) {
        props.filters.services.forEach(id => allServiceIds.add(id))
      }
      if (props.filters.budget) params.append('budget', props.filters.budget)
      if (props.filters.minRating) params.append('avgRating[gte]', props.filters.minRating)
      if (props.filters.maxRating) params.append('avgRating[lte]', props.filters.maxRating)

      if (props.filters.sortField && props.filters.sortDirection) {
        params.append(`order[${props.filters.sortField}]`, props.filters.sortDirection);
      }
      allServiceIds.forEach(id => params.append('id_services[]', id));
      if ([...params].length > 0) url += `&${params.toString()}`
      return url
    }

    const loadPricesAndNames = async (masters) => {
      const sIds = ServiceIds.value;
      if (!masters.length || !sIds.length) return;

      for (const id of sIds) {
        if (!serviceNames.value[id]) {
          await fetchData(`/api/v1/services/${id}`, {}, data => {
            serviceNames.value[id] = data.name;
          });
        }
      }

      const priceParams = new URLSearchParams();
      masters.forEach(m => priceParams.append('master.id[]', m.id));
      sIds.forEach(id => priceParams.append('service.id[]', id));

      const raw = await fetchData('/api/v1/masters_services', priceParams, d => d['member'] || d['hydra:member'] || []);

      raw?.forEach(item => {
        const mId = typeof item.masterId === 'string' ? item.masterId.split('/').pop() : item.masterId;
        const sId = typeof item.serviceId === 'string' ? item.serviceId.split('/').pop() : item.serviceId;
        pricesMap.value[`${mId}_${sId}`] = item.price;
      });
    };

    const loadLocations = async (masters) => {
      const newCityIds = [...new Set(masters.map(m => m.cityId).filter(id => id && !cityNames.value[id]))];
      const newDistrictIds = [...new Set(masters.map(m => m.districtId).filter(id => id && !districtNames.value[id]))];

      if (newCityIds.length > 0) {
        const params = new URLSearchParams();
        newCityIds.forEach(id => params.append('id[]', id));

        await fetchData('/api/v1/cities', params, data => {
          const items = data['member'] || data['hydra:member'] || [];
          items.forEach(c => { cityNames.value[c.id] = c.city; });
        });
      }

      if (newDistrictIds.length > 0) {
        const params = new URLSearchParams();
        newDistrictIds.forEach(id => params.append('id[]', id));

        await fetchData('/api/v1/districts', params, data => {
          const items = data['member'] || data['hydra:member'] || [];
          items.forEach(d => { districtNames.value[d.id] = d.name; });
        });
      }
    };

    const fetchGroomers = async (page = 1, reset = false) => {
      const params = new URLSearchParams();

      params.append('page', page);

      const allServiceIds = new Set(props.filters.services || []);
      if (props.filters.service) allServiceIds.add(props.filters.service);

      if (props.filters.search?.length >= 3) params.append('search', props.filters.search);
      if (props.filters.city) params.append('id_city.id', props.filters.city);
      if (props.filters.district) params.append('district.id', props.filters.district);
      if (props.filters.breed) params.append('id_pets.id', props.filters.breed);
      if (props.filters.budget) params.append('budget', props.filters.budget);
      if (props.filters.minRating) params.append('avgRating[gte]', props.filters.minRating);
      if (props.filters.maxRating) params.append('avgRating[lte]', props.filters.maxRating);
      if (props.filters.date_from) params.append('date_from', props.filters.date_from);
      if (props.filters.date_to) params.append('date_to', props.filters.date_to);
      if (props.filters.start_time) params.append('start_time', props.filters.start_time);
      if (props.filters.end_time) params.append('end_time', props.filters.end_time);

      allServiceIds.forEach(id => {
        params.append('id_services[]', id);
        params.append('services[]', id);
      });

      if (props.filters.sortField && props.filters.sortDirection) {
        params.append(`order[${props.filters.sortField}]`, props.filters.sortDirection);
      }

      const data = await fetchData('/api/v1/masters', params, d => d);
      if (!data) return;

      const items = data['member'] || data['hydra:member'] || data;

      if (reset) groomers.value = items;
      else groomers.value.push(...items);

      await Promise.all([
        loadPricesAndNames(items),
        loadLocations(items)
      ]);

      currentPage.value = page;
      lastPage.value = data['view']?.last
          ? parseInt(new URL(data['view'].last, window.location.origin).searchParams.get('page'))
          : page;
    }

    const loadMore = () => {
      if (currentPage.value < lastPage.value) {
        fetchGroomers(currentPage.value + 1)
      }
    }

    const hasMore = computed(() => currentPage.value < lastPage.value)
    const openBookingModal = async (groomer) => {
      selectedGroomer.value = groomer;
      await nextTick();
      if (bookingForm.value?.open) {
        bookingForm.value.open();
      }
    };

    const closeBookingModal = () => {
      if (bookingForm.value?.close) {
        bookingForm.value.close();
      }
    };

    onMounted(() => fetchGroomers(1, true))

    watch(
        () => props.filters,
        () => {
          currentPage.value = 1
          lastPage.value = 1
          pricesMap.value = {}
          fetchGroomers(1, true)
        },
        { deep: true }
    )

    const selectedServiceName = computed(() => props.filters.serviceName || null)

    return { groomers, selectedServiceName, hasMore, loadMore, selectedGroomer, bookingForm, openBookingModal, closeBookingModal, ServiceIds, serviceNames, pricesMap, cityNames, districtNames }
  }
}
</script>

<style>

</style>