<template>
  <div id="filters" class="container-wide">
    <filter-panel @search="onSearch"></filter-panel>
  </div>
  <div id="groomers-list">
    <groomer-list :filters="filters"></groomer-list>
  </div>
</template>

<script>
import { ref } from 'vue'
import FilterPanel from './FilterPanel.vue'
import GroomerList from './GroomerList.vue'

export default {
  components: { FilterPanel, GroomerList },
  setup() {
    const filters = ref({})

    const onSearch = (searchParams) => {
      filters.value = searchParams
    }

    return { filters, onSearch }
  }
}
</script>

<style>

</style>