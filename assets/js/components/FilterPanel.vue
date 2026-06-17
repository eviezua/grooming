<template>
  <div class="filter-grid my-5">
    <BaseDropdown
        :options="cities"
        v-model="selectedCity"
        :label="$t('City')"
        resource="cities"
    />
    <BaseDropdown
        :options="districts"
        v-model="selectedDistrict"
        :label="$t('District')"
        resource="cities"
    />
    <BaseDropdown
        :options="breeds"
        v-model="selectedBreed"
        :label="$t('Breed')"
        resource="pets"
    />
    <BaseDropdown
        :options="services"
        v-model="selectedService"
        :label="$t('Service')"
        resource="services"
    />
    <SearchInput v-model="search" />
    <a
        href="#"
        class="search-button orange-but justify-content-center align-items-center text-white text-decoration-none inter-18"
        @click.prevent="onSearchClick"
    >
      {{ $t('Search') }}
    </a>
   <div class="btn-secondary white-but inter-18 text-black d-flex justify-content-between align-items-center" style=" padding: 20px 30px; grid-row: auto; grid-column: 1 / -1; "
        @click="openOtherFilters">
      <span>{{ $t('Other filters') }}</span>
      <img src="/uploads/icons/filter.png" class="icon">
    </div>
  </div>
  <OtherFiltersForm ref="otherFiltersForm" @apply="onApplyOtherFilters"/>
</template>

<script>
import BaseDropdown from './BaseDropdown.vue';
import SearchInput from './SearchInput.vue';
import OtherFiltersForm from "./OtherFiltersForm.vue";
import {ref, onMounted, watch, nextTick} from 'vue';
import { useApiFetch } from "../useFetchResource";

export default {
  components: {
    BaseDropdown,
    SearchInput,
    OtherFiltersForm
  },
  setup(_, { emit }) {
    const cities = ref([])
    const districts = ref([])
    const breeds = ref([])
    const services = ref([])
    const otherFiltersForm = ref(null)
    const otherFilters = ref({
      services: [],
      budget: null,
      minRating: 0,
      maxRating: 5,
      date_from: null,
      date_to: null,
      start_time: null,
      end_time: null,
      sortField: null,
      sortDirection: null
    })

    const selectedCity = ref(null)
    const selectedDistrict = ref(null)
    const selectedBreed = ref(null)
    const selectedService = ref(null)
    const search = ref('')
    const { fetchData, loading } = useApiFetch();

    const mapItems = (json, mapFn) => {
      const items = json['hydra:member'] || json['member'] || [];
      return items.map(mapFn);
    };

    const fetchDistrictsByCity = async (cityId) => {
      if (!cityId) {
        districts.value = []
        selectedDistrict.value = null
        return
      }
      const data = await fetchData(
          '/api/v1/districts',
          { 'city.id[]': cityId },
          json => mapItems(json, d => ({ id: d.id, name: d.name }))
      )

      if (data) districts.value = data
      selectedDistrict.value = null
    }

    const onSearchClick = () => {
      const servicesArray = [...(otherFilters.value.services || [])];

      if (selectedService.value?.id && !servicesArray.includes(selectedService.value.id)) {
        servicesArray.push(selectedService.value.id);
      }
      const searchPayload = {
        city: selectedCity.value?.id || null,
        district: selectedDistrict.value?.id || null,
        breed: selectedBreed.value?.id || null,
        service: selectedService.value?.id || null,
        serviceName: selectedService.value?.name || null,
        search: search.value || null,

        services: servicesArray,
        budget: otherFilters.value.budget,
        minRating: otherFilters.value.minRating,
        maxRating: otherFilters.value.maxRating,
        date_from: otherFilters.value.date_from,
        date_to: otherFilters.value.date_to,
        start_time: otherFilters.value.start_time,
        end_time: otherFilters.value.end_time,

        sortField: otherFilters.value.sortField || null,
        sortDirection: otherFilters.value.sortDirection || null
      }
      console.log(searchPayload)
      emit('search', searchPayload)
    }

    const onApplyOtherFilters = (filters) => {
      otherFilters.value = filters
      onSearchClick()
    }

    const openOtherFilters = async () => {
        await nextTick();
        if (otherFiltersForm.value?.open) {
          otherFiltersForm.value.open();
        }
    }

    onMounted(() => {
      fetchData('/api/v1/cities', {}, j => mapItems(j, i => ({ id: i.id, name: i.city }))).then(d => cities.value = d || [])
      fetchData('/api/v1/districts', {}, j => mapItems(j, i => ({ id: i.id, name: i.name }))).then(d => districts.value = d || [])
      fetchData('/api/v1/pets', {}, j => mapItems(j, i => ({ id: i.id, name: `${i.spice} — ${i.breed}` }))).then(d => breeds.value = d || [])
      fetchData('/api/v1/services', {}, j => mapItems(j, i => ({ id: i.id, name: i.name }))).then(d => services.value = d || [])
    })

    watch(selectedCity, (newCity) => {
      fetchDistrictsByCity(newCity?.id)
    })

    return { loading, cities, districts, breeds, services, selectedCity, selectedDistrict, selectedBreed, selectedService, search, onSearchClick, openOtherFilters, otherFiltersForm, onApplyOtherFilters}
  }
}
</script>

<style lang="scss">

.search-button{
  position: relative;
  grid-column: 6 / 7;
  display: flex;
  cursor: pointer;
  z-index: 300;
  justify-self: end;
  height: 73px;
  min-width: 120px;
  width: 100%;
}

.filter-grid > * {
  min-width: 0;
}

.filter-grid {
  position: relative;
  display: grid;
  grid-template-columns: repeat(4, minmax(150px, 1fr)) 72px 200px;
  gap: 20px;
  align-items: center;
  width: 100%;
  .search-cell {
    grid-column: span 1;
    transition: grid-column 0.4s ease;
  }

  .search-cell.expanded {
    grid-column: 1 / 6;
  }
}
@media (max-width: 992px) {
  .filter-grid{
    grid-template-columns: none;
  }
}

@media (max-width: 576px) {
  .filter-grid{
    grid-template-columns: 1fr !important;
  }

  .filter-grid > * {
    grid-column: auto !important;
  }

  .search-button {
    justify-self: stretch;
  }
}
</style>