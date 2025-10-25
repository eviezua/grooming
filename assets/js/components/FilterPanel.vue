<template>
  <div class="filter-grid my-5">
    <BaseDropdown
        :options="cities"
        v-model="selectedCity"
        label="Місто"
        resource="cities"
    />
    <BaseDropdown
        :options="districts"
        v-model="selectedDistrict"
        label="Район"
        resource="cities"
    />
    <BaseDropdown
        :options="breeds"
        v-model="selectedBreed"
        label="Порода"
        resource="pets"
    />
    <BaseDropdown
        :options="services"
        v-model="selectedService"
        label="Послуга"
        resource="services"
    />
    <SearchInput v-model="search" />
    <a
        href="#"
        class="search-button orange-but justify-content-center align-items-center text-white text-decoration-none inter-18"
        @click.prevent="onSearchClick"
    >
      Знайти
    </a>
   <div class="btn-secondary white-but inter-18 text-black d-flex justify-content-between align-items-center" style=" padding: 20px 30px; grid-row: auto; grid-column: 1 / -1; ">
      <span>Інші фільтри</span>
      <img src="/uploads/icons/filter.png" class="icon">
    </div>
  </div>
</template>

<script>
import BaseDropdown from './BaseDropdown.vue'
import SearchInput from './SearchInput.vue'
import { ref, onMounted, watch } from 'vue'

export default {
  components: {
    BaseDropdown,
    SearchInput
  },
  setup(_, { emit }) {
    const cities = ref([])
    const districts = ref([])
    const breeds = ref([])
    const services = ref([])

    const selectedCity = ref(null)
    const selectedDistrict = ref(null)
    const selectedBreed = ref(null)
    const selectedService = ref(null)
    const search = ref('')

    const fetchOptions = async (url, targetRef, mapFn) => {
      const res = await fetch(url)
      const json = await res.json()
      const data = json['hydra:member'] || json['member'] || []
      targetRef.value = data.map(mapFn)
    }

    const fetchDistrictsByCity = async (cityId) => {
      if (!cityId) {
        districts.value = []
        selectedDistrict.value = null
        return
      }
      await fetchOptions(
          `/api/v1/districts?city.id[]=${cityId}`,
          districts,
          i => ({ id: i.id, name: i.name })
      )
      selectedDistrict.value = null
    }

    const onSearchClick = () => {
      emit('search', {
        city: selectedCity.value?.id || null,
        district: selectedDistrict.value?.id || null,
        breed: selectedBreed.value?.id || null,
        service: selectedService.value?.id || null,
        serviceName: selectedService.value?.name || null,
        search: search.value || null,
      })
    }

    onMounted(() => {
      fetchOptions('/api/v1/cities', cities, i => ({ id: i.id, name: i.city }))
      fetchOptions('/api/v1/districts', districts, i => ({ id: i.id, name: i.name }))
      fetchOptions('/api/v1/pets', breeds, i => ({ id: i.id, name: `${i.spice} — ${i.breed}` }))
      fetchOptions('/api/v1/services', services, i => ({ id: i.id, name: i.name }))
    })

    watch(selectedCity, (newCity) => {
      fetchDistrictsByCity(newCity?.id)
    })

    return {
      cities,
      districts,
      breeds,
      services,
      selectedCity,
      selectedDistrict,
      selectedBreed,
      selectedService,
      search,
      onSearchClick
    }
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