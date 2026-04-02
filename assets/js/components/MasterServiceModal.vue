<template>
  <div v-if="modelValue" class="modal" @click.self="close">
    <div class="modal-dialog animated-scale">
      <div class="modal-content p-4 bg-white shadow-lg border-0">
        <div class="modal-header border-0 justify-content-between pb-0">
          <h4 class="inter-22 mb-0">Додати нову послугу</h4>
          <button class="btn-close" @click="close"></button>
        </div>

        <div class="modal-body py-4">
          <div class="mb-4">
            <label class="gray-text inter-14 mb-2">Оберіть послугу з каталогу</label>
            <VueMultiselect
                v-model="selectedServiceId"
                :options="availableServices.map(s => s.id)"
                :custom-label="id => {
                  const s = catalogOptions.find(x => x.id === id);
                  return s ? `${s.name} (сер. ціна: ${s.cost} ₴)` : 'Оберіть послугу';
                }"
                placeholder="Почніть вводити назву..."
                :searchable="true"
                :internal-search="false"
                @search-change="onSearchService"
                :show-labels="false"
            />
          </div>

          <div v-if="selectedService" class="selected-area p-3 mb-4 rounded-4 bg-light">
            <div class="inter-14 gray-text mb-1">Середня вартість на ринку:</div>
            <div class="inter-20 orange-text">{{ selectedService.cost }} ₴</div>
          </div>

          <div class="mb-2">
            <label class="gray-text inter-14 mb-2">Ваша базова ціна (₴)</label>
            <input
                type="number"
                v-model.number="price"
                class="form-control rounded-4 shadow-none border-light"
                placeholder="Введіть суму"
            >
          </div>
        </div>

        <div class="modal-footer border-0 d-flex justify-content-end gap-2 pt-2">
          <button class="btn btn-light rounded-4 px-4" @click="close">Скасувати</button>
          <button
              class="orange-but py-2 px-4 text-white border-0"
              @click="handlePost"
              :disabled="loading || !selectedServiceId"
          >
            <span v-if="loading" class="spinner-border spinner-border-sm me-1"></span>
            Додати послугу
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import VueMultiselect from "vue-multiselect";
import { useApiFetch } from "../useFetchResource";
import { useSubmit } from "../usePostResource";
import { useDebounce } from "../useDebounce";

const props = defineProps({
  modelValue: Boolean,
  masterId: Number,
  existingServiceIds: Array
});

const emit = defineEmits(['update:modelValue', 'added']);
const { fetchData } = useApiFetch();
const { submit, loading } = useSubmit();

const selectedServiceId = ref(null);
const price = ref(0);

const catalogOptions = ref([]);
const currentSearchIds = ref([]);

const close = () => emit('update:modelValue', false);

const availableServices = computed(() => {
  return catalogOptions.value.filter(s => {
    const isFoundNow = currentSearchIds.value.includes(s.id);
    const isNotAdded = !props.existingServiceIds.includes(s.id);
    const isSelected = s.id === selectedServiceId.value;

    return (isFoundNow && isNotAdded) || isSelected;
  });
});

const selectedService = computed(() =>
    catalogOptions.value.find(s => s.id === selectedServiceId.value)
);

const onSearchService = useDebounce((query) => {
  fetchCatalog(query);
}, 300);

const fetchCatalog = async (search = "") => {
  const params = { page: 1 };

  if (search && search.length >= 3) {
    params.search = search;
  }

  const data = await fetchData('/api/v1/services', params);
  const items = data?.['hydra:member'] || data?.member || [];

  const fetchedItems = items.map(s => ({
    id: s.id,
    name: s.name,
    cost: s.cost
  }));

  currentSearchIds.value = fetchedItems.map(i => i.id);

  const merged = [...catalogOptions.value, ...fetchedItems];
  catalogOptions.value = Array.from(new Map(merged.map(item => [item.id, item])).values());
};

watch(() => props.modelValue, (val) => {
  if (val) {
    selectedServiceId.value = null;
    price.value = 0;
    catalogOptions.value = [];
    fetchCatalog();
  }
});

const handlePost = async () => {
  const payload = {
    masterId: Number(props.masterId),
    serviceId: Number(selectedServiceId.value),
    price: Number(price.value)
  };

  const data = await submit({
    url: '/api/v1/masters_services',
    required: ['serviceId', 'price'],
    payload,
    successMessage: "Послугу додано до вашого прайсу!"
  });

  if (data) {
    emit('added', data);
    close();
  }
};
</script>