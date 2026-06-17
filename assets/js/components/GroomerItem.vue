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
    <a class="mb-3" href="#"  @click.prevent="$emit('open-booking', groomer)">{{ $t('Select time') }} <img src="/uploads/icons/arrow_black.png"></a>
  </div>
</template>

<script>
import filterPanel from "./FilterPanel.vue";
import {computed} from "vue";

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
    selectedServicesIds: {
      type: Array,
      default: []
    },
    serviceNames: {
      type: Object,
      default: () => ({})
    },
    pricesMap: {
      type: Object,
      default: () => ({})
    },
    cityNames: {
      type: Object,
      default: () => ({})
    },
    districtNames: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const filteredServices = computed(() => {
      return props.selectedServicesIds.map(sId => {
        const price = props.pricesMap[`${props.groomer.id}_${sId}`];
        if (price !== undefined) {
          return {
            id: sId,
            serviceName: props.serviceNames[sId] || '...',
            price: price
          };
        }
        return null;
      }).filter(s => s !== null);
    });

    const cityName = computed(() => props.cityNames[props.groomer.cityId] || "...");
    const districtName = computed(() => props.districtNames[props.groomer.districtId] || "");

    return { cityName, districtName, filteredServices }
  }
}
</script>

<style>

</style>