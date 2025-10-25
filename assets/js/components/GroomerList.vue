<template>
  <div class="hero-masters-wrapper container-wide">
    <groomer-item
        v-for="g in groomers"
        :key="g.id || g.name"
        :groomer="g"
        :selected-service="selectedServiceName"
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
    const groomers = ref([])
    const currentPage = ref(1)
    const lastPage = ref(1)
    const loading = ref(false)
    const bookingForm = ref(null)
    const selectedGroomer = ref(null)

    const buildUrl = (page = 1) => {
      let url = `/api/v1/masters?page=${page}`
      const params = new URLSearchParams()
      if (props.filters.search && props.filters.search.length >= 3) params.append('search', props.filters.search)
      if (props.filters.city) params.append('id_city.id', props.filters.city)
      if (props.filters.district) params.append('district.id', props.filters.district)
      if (props.filters.breed) params.append('id_pets.id', props.filters.breed)
      if (props.filters.service) params.append('id_services', props.filters.service)
      if ([...params].length > 0) url += `&${params.toString()}`
      return url
    }

    const fetchGroomers = async (page = 1, reset = false) => {
      if (loading.value) return
      loading.value = true
      try {
        const res = await fetch(buildUrl(page))
        if (!res.ok) throw new Error(res.statusText)
        const data = await res.json()
        const items = data['member'] || data['hydra:member'] || data

        if (reset) groomers.value = items
        else groomers.value.push(...items)

        currentPage.value = page
        lastPage.value = data['view']?.last
            ? parseInt(new URL(data['view'].last, window.location.origin).searchParams.get('page'))
            : page
      } catch (e) {
        console.error(e)
      } finally {
        loading.value = false
      }
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
          fetchGroomers(1, true)
        },
        { deep: true }
    )

    const selectedServiceName = computed(() => props.filters.serviceName || null)

    return { groomers, selectedServiceName, hasMore, loadMore, selectedGroomer, bookingForm, openBookingModal, closeBookingModal }
  }
}
</script>

<style>

</style>