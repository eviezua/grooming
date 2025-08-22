<template>
  <div class="hero-masters-wrapper container-wide">
    <groomer-item
        v-for="g in groomers"
        :key="g.id || g.name"
        :groomer="g"
    />
  </div>
</template>

<script>
import GroomerItem from './GroomerItem.vue'
import { ref, onMounted, watch } from 'vue'

export default {
  components: { GroomerItem },
  props: {
    filters: {
      type: Object,
      default: () => ({})
    }
  },
  setup(props) {
    const groomers = ref([])

    const fetchGroomers = async () => {
      let url = '/api/v1/masters'

      const params = new URLSearchParams()
      if (props.filters.search && props.filters.search.length >= 3) params.append('search', props.filters.search)
      if (props.filters.city) params.append('id_city.id', props.filters.city)
      if (props.filters.district) params.append('district', props.filters.district)
      if (props.filters.breed) params.append('id_pets.id', props.filters.breed)
      if (props.filters.service) params.append('id_services.id', props.filters.service)

      if ([...params].length > 0) {
        url += `?${params.toString()}`
      }

      const res = await fetch(url)
      const data = await res.json()
      groomers.value = data['hydra:member'] || data['member'] || data
    }

    onMounted(fetchGroomers)

    watch(() => props.filters, fetchGroomers, { deep: true })

    return { groomers }
  }
}
</script>

<style>
  
</style>