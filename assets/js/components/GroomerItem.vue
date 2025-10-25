<template>
  <div class="hero-master bg-white position-relative p-5 my-5">
    <div class="d-flex align-items-center mt-3 gap-2">
      <h3 class="mb-0">{{ $t('Rating:') }}</h3>
      <star-rating :model-value="Number(groomer.avgRating)" :readonly="true"></star-rating>
      <div class="circle-block d-flex position-absolute">
        <img :src="`/uploads/photos/${groomer.photo}`" :alt="groomer.name">
      </div>
    </div>
    <h2 class="mb-2 mt-4">{{ groomer.name }} {{ groomer.surname }}</h2>
    <h3 class="gray-text">{{ cityName }}, {{ districtName }}, {{ groomer.address }}</h3>
    <h3 class="my-4" v-for="s in filteredServices" :key="s.id">{{ s.serviceName }} — {{ s.price }} грн</h3>
    <a class="mb-3" href="#"  @click.prevent="$emit('open-booking', groomer)">Обрати час <img src="/uploads/icons/arrow_black.png"></a>
  </div>
</template>

<script>
import filterPanel from "./FilterPanel.vue";
import { ref, onMounted, computed } from "vue";

export default {
  computed: {
    filterPanel() {
      return filterPanel
    }
  },
  props: {
    groomer: {
      type: Object,
      required: true
    },
    selectedService: {
      type: String,
      default: null
    }
  },
  setup(props) {
    const cityName = ref("")
    const districtName = ref("")
    const servicesWithPrices = ref([])
    const filteredServices = computed(() => {
      if (!props.selectedService) return []
      return servicesWithPrices.value.filter(s => s.serviceName === props.selectedService)
    })


    async function loadServices() {
      try {
        const res = await fetch(`/api/v1/masters_services?master.id=${props.groomer.id}`)
        const data = await res.json()
        const rawServices = data['hydra:member'] || data['member'] || data

        const servicesWithNames = await Promise.all(
            rawServices.map(async s => {
              try {
                const srvRes = await fetch(`/api/v1/services/${s.serviceId}`)
                const srvData = await srvRes.json()
                return { ...s, serviceName: srvData.name }
              } catch {
                return { ...s, serviceName: 'Unknown' }
              }
            })
        )

        servicesWithPrices.value = servicesWithNames
      } catch (e) {
        servicesWithPrices.value = []
      }
    }

    async function loadCity() {
      try {
        const res = await fetch(`/api/v1/cities/${props.groomer.cityId}`)
        const data = await res.json()
        cityName.value = data.city
      } catch (e) {
        cityName.value = "Unknown"
      }
    }

    async function loadDistrict() {
      if (!props.groomer.districtId) return
      try {
        const res = await fetch(`/api/v1/districts/${props.groomer.districtId}`)
        const data = await res.json()
        districtName.value = data.name
      } catch (e) {
        districtName.value = "Unknown"
      }
    }

    onMounted(() => {
      if (props.groomer.cityId) loadCity()
      if (props.groomer.districtId) loadDistrict()
      loadServices()
    })

    return { cityName, districtName, servicesWithPrices, filteredServices }
  }
}
</script>

<style>

</style>