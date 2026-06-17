<template>
  <div class="mt-2 scale-in d-flex flex-wrap align-items-center gap-2">
    <div class="flex-grow-1" style="max-width: 220px;">
      <BaseDropdown
          v-model="selectedCity"
          :options="cityOptions"
          :label="$t('City')"
          resource="cities"
      />
    </div>
    <div class="flex-grow-1" style="max-width: 220px;">
      <BaseDropdown
          :key="districtOptions.length"
          v-model="selectedDistrict"
          :options="districtOptions"
          :label="$t('District')"
          resource="districts"
          :disabled="!selectedCity"
      />
    </div>
    <div class="d-flex gap-2">
      <button @click="handleSave" class="btn-confirm-small" :disabled="updating || !isReady">✓</button>
      <button @click="$emit('cancel')" class="btn-cancel-small">✕</button>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, computed } from 'vue';
import BaseDropdown from "./BaseDropdown.vue";

const props = defineProps({
  cityOptions: Array,
  initialCity: Object,
  initialDistrict: Object,
  updating: Boolean,
  fetchData: Function
});

const emit = defineEmits(['save', 'cancel']);

const selectedCity = ref(props.initialCity);
const selectedDistrict = ref(props.initialDistrict);
const districtOptions = ref([]);

const isReady = computed(() => selectedCity.value && selectedDistrict.value);

watch(selectedCity, async (newCity) => {
  if (!newCity) {
    districtOptions.value = [];
    selectedDistrict.value = null;
    return;
  }

  if (props.initialCity?.id !== newCity.id) {
    selectedDistrict.value = null;
  }

  const data = await props.fetchData(
      '/api/v1/districts',
      { 'city.id[]': newCity.id },
      j => (j['hydra:member'] || j['member'] || []).map(d => ({ id: d.id, name: d.name }))
  );

  if (data) {
    districtOptions.value = data;
    if (props.initialDistrict && data.find(d => d.id === props.initialDistrict.id)) {
      selectedDistrict.value = props.initialDistrict;
    }
  }
}, { immediate: true });

const handleSave = () => {
  emit('save', {
    city: selectedCity.value,
    district: selectedDistrict.value
  });
};
</script>