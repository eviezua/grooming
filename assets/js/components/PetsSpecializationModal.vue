<template>
  <div class="modal" @click.self="$emit('close')">
    <div class="modal-dialog animated-scale">
      <div class="modal-content p-4">

        <div class="modal-header border-0 pb-0">
          <h4 class="inter-22 mb-0">{{ $t('Master`s specialization') }}</h4>
          <button @click="$emit('close')" class="btn-close-custom">✕</button>
        </div>

        <div class="modal-body py-4">
          <div class="selected-area p-3 mb-4 rounded-4">
            <div v-if="selectedPetsForEdit.length === 0" class="gray-text text-center">{{ $t('Nothing selected') }}</div>
            <div v-for="(pets, spice) in groupedSelectedPets" :key="spice" class="mb-3">
              <div class="inter-14 fw-bold orange-text mb-2 text-uppercase">{{ spiceLabels[spice] || spice }}</div>
              <div class="d-flex flex-wrap gap-2">
                <span v-for="pet in pets" :key="pet.id" class="pet-tag-edit">
                  {{ pet.name }}
                  <button @click="removePet(pet.id)" class="btn-remove-pet">✕</button>
                </span>
              </div>
            </div>
          </div>

          <div class="row g-3">
            <div class="col-md-5">
              <VueMultiselect
                  v-model="selectedSpecies"
                  :options="speciesOptions"
                  label="name"
                  track-by="id"
                  :placeholder="$t('Spice')"
                  :show-labels="false"
              />
            </div>
            <div class="col-md-7">
              <VueMultiselect
                  v-model="newBreedToAdd"
                  :options="filteredBreedOptions"
                  label="name"
                  track-by="id"
                  :searchable="true"
                  :internal-search="false"
                  @search-change="onSearchBreed"
                  @select="addNewPet"
                  :disabled="!selectedSpecies"
                  :placeholder="$t('Start enter name...')"
                  :show-labels="false"
              />
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 d-flex justify-content-end pt-0">
          <button class="orange-but py-2 px-5 text-white" @click="handleSave" :disabled="updating">
            {{ updating ? $t('Loading...') : $t('Save') }}
          </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import VueMultiselect from "vue-multiselect";
import { useDebounce } from "../useDebounce";

const props = defineProps(['initialPets', 'spiceLabels', 'speciesOptions', 'updating', 'fetchData']);
const emit = defineEmits(['close', 'save']);

const selectedPetsForEdit = ref([...props.initialPets]);
const selectedSpecies = ref(null);
const breedOptions = ref([]);
const newBreedToAdd = ref(null);

const onSearchBreed = useDebounce(s => fetchBreeds(selectedSpecies.value?.id, s), 300);

const fetchBreeds = async (spiceId, search = "") => {
  if (!spiceId) return;

  const params = {
    page: 1,
    spice: spiceId
  };

  if (search.length > 3) {
    params.search = search;
  }

  const data = await props.fetchData('/api/v1/pets', params);
  const items = data?.['hydra:member'] || data?.member || [];

  breedOptions.value = items.map(p => ({
    id: p.id,
    name: p.breed,
    spice: p.spice
  }));
};

const groupedSelectedPets = computed(() => {
  const groups = {};
  selectedPetsForEdit.value.forEach(p => {
    if (!groups[p.spice]) groups[p.spice] = [];
    groups[p.spice].push(p);
  });
  return groups;
});

const filteredBreedOptions = computed(() => breedOptions.value.filter(o => !selectedPetsForEdit.value.some(p => p.id === o.id)));

const addNewPet = (p) => { if (p) selectedPetsForEdit.value.push(p); setTimeout(() => newBreedToAdd.value = null, 50); };
const removePet = (id) => selectedPetsForEdit.value = selectedPetsForEdit.value.filter(p => p.id !== id);
const handleSave = () => emit('save', selectedPetsForEdit.value.map(p => p.id), selectedPetsForEdit.value);

watch(() => props.initialPets, (newPets) => {
  selectedPetsForEdit.value = [...newPets];
}, { deep: true });

watch(selectedSpecies, (newSpecies) => {
  if (newSpecies) {
    fetchBreeds(newSpecies.id);
  } else {
    breedOptions.value = [];
  }
});
</script>

<style lang="scss" scoped>
.modal-content {
  border-radius: 30px;
  box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

.btn-close-custom {
  background: none;
  border: none;
  font-size: 1.5rem;
  color: #888;
  cursor: pointer;
  &:hover { color: #333; }
}

.selected-area {
  background-color: #fcfcfc !important;
  border: 2px dashed #eee;
  overflow-y: auto;
  max-height: 250px;
}

.pet-tag-edit {
  background: white;
  border: 1px solid #FF9229;
  padding: 5px 12px;
  border-radius: 10px;
  font-size: 14px;
  display: flex;
  align-items: center;
  gap: 8px;

  .btn-remove-pet {
    background: none;
    border: none;
    color: #ff4d4d;
    font-weight: bold;
    padding: 0;
    cursor: pointer;
  }
}

.orange-but {
  background: #FF9229;
  border: none;
  border-radius: 12px;
  font-weight: bold;
  transition: 0.3s;
  &:hover:not(:disabled) {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(255, 146, 41, 0.4);
  }
  &:disabled { opacity: 0.7; }
}

.orange-text { color: #FF9229; }
.gray-text { color: #888; }

.animated-scale {
  animation: scaleIn 0.3s ease-out;
}

@keyframes scaleIn {
  from { opacity: 0; transform: scale(0.95); }
  to { opacity: 1; transform: scale(1); }
}
</style>