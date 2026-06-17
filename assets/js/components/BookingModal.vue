<template>
  <div v-if="modelValue" class="modal" @click.self="close">
    <div class="modal-dialog animated-scale">
      <div class="modal-content p-4 bg-white shadow-lg border-0">
        <div class="modal-header border-0 justify-content-between pb-0">
          <h4 class="inter-22 mb-0">{{ editingBooking ? $t('Booking details') : $t('New booking') }}</h4>
          <button class="btn-close" @click="close"></button>
        </div>

        <div class="modal-body py-4">
          <div class="selected-area p-3 mb-4 rounded-4">
            <div class="mb-3">
              <div class="inter-14 fw-bold orange-text mb-2 text-uppercase">{{ $t('Selected client') }}</div>
              <div v-if="editForm.clientId" class="pet-tag-edit">
                {{ clientNames[editForm.clientId] || $t('Loading...') }}
              </div>
              <div v-else class="gray-text inter-14">{{ $t('No client selected') }}</div>
            </div>
            <div class="mb-2">
              <div class="inter-14 fw-bold orange-text mb-2 text-uppercase">{{ $t('Clients pet') }}</div>
              <div v-if="editForm.petId" class="pet-tag-edit">
                {{ petBreeds[editForm.petId] || $t('Select pet below') }}
              </div>
              <div v-else class="gray-text inter-14">{{ $t('No pet selected') }}</div>
            </div>
          </div>

          <div class="row g-3 mb-4">
            <div class="col-md-6">
              <label class="gray-text inter-14 mb-2">{{ editingBooking ? $t('Change client') : $t('Select client') }}</label>
              <VueMultiselect
                  v-model="editForm.clientId"
                  :options="allClients.map(c => c.id)"
                  :custom-label="id => {
                  const c = allClients.find(x => x.id === id);
                  return c ? `${c.name} ${c.surname}` :  $t('Select client');
                }"
                  :placeholder="$t('Searching client...')"
                  :show-labels="false"
              />
            </div>
            <div class="col-md-6">
              <label class="gray-text inter-14 mb-2">{{ editingBooking ? $t('Change pet') : $t('Select pet') }}</label>
              <VueMultiselect
                  v-model="editForm.petId"
                  :options="currentClientPets.map(p => p.id)"
                  :custom-label="id => currentClientPets.find(x => x.id === id)?.breed ||  $t('Breed')"
                  :disabled="!editForm.clientId"
                  :placeholder="$t('Select pet')"
                  :show-labels="false"
              />
            </div>
          </div>

          <div class="mb-4">
            <label class="gray-text inter-14 mb-2">{{ $t('Services') }}</label>
            <VueMultiselect
                v-model="editForm.services"
                :options="allServices.map(s => s.id)"
                :custom-label="id => allServices.find(x => x.id === id)?.name || $t('Service name')"
                :multiple="true"
                :close-on-select="false"
                :placeholder="$t('Select services')"
                :show-labels="false"
            />
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="gray-text inter-14 mb-2">{{ $t('Date') }}</label>
              <input type="date" v-model="editForm.date" class="form-control rounded-4 shadow-none border-light">
            </div>
            <div class="col-md-3 col-6">
              <label class="gray-text inter-14 mb-2">{{ $t('Start') }}</label>
              <input type="time" v-model="editForm.timeStart" class="form-control rounded-4 shadow-none border-light">
            </div>
            <div class="col-md-3 col-6">
              <label class="gray-text inter-14 mb-2">{{ $t('Stop') }}</label>
              <input type="time" v-model="editForm.timeStop" class="form-control rounded-4 shadow-none border-light">
            </div>
          </div>
        </div>

        <div class="modal-footer border-0 d-flex justify-content-between align-items-center pt-2">
          <div>
            <button v-if="editingBooking" class="btn-delete-text" @click="handleDelete">{{ $t('Delete booking') }}</button>
          </div>
          <div class="d-flex gap-2">
            <button class="btn btn-light rounded-4 px-4" @click="close">{{ $t('Cancel') }}</button>
            <button class="orange-but py-2 px-4 text-white border-0" @click="handleSave" :disabled="updating || creating">
              <span v-if="updating || creating" class="spinner-border spinner-border-sm me-1"></span>
              {{ editingBooking ? $t('Save') : $t('Create') }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import VueMultiselect from "vue-multiselect";
import { useApiFetch } from "../useFetchResource";
import { useDelete } from "../useDeleteResource";
import { useUpdate } from "../useUpdateResource";
import { useSubmit } from "../usePostResource";

const props = defineProps({
  modelValue: Boolean,
  editingBooking: Object,
  masterId: Number,
  clientNames: Object,
  petBreeds: Object
});

const emit = defineEmits(['update:modelValue', 'saved', 'deleted']);

const { fetchData } = useApiFetch();
const { remove, loading: deleting } = useDelete();
const { updateMaster: updateBooking, loading: updating } = useUpdate();
const { submit: createBooking, loading: creating } = useSubmit();

const editForm = ref({});
const allClients = ref([]);
const currentClientPets = ref([]);
const allServices = ref([]);

const close = () => emit('update:modelValue', false);

const loadInitialData = async () => {
  const cData = await fetchData('/api/v1/clients');
  allClients.value = cData?.['hydra:member'] || cData?.member || [];
  const sData = await fetchData('/api/v1/services');
  allServices.value = sData?.['hydra:member'] || sData?.member || [];
};

watch(() => props.modelValue, async (val) => {
  if (val) {
    await loadInitialData();
    if (props.editingBooking) {
      editForm.value = {
        ...props.editingBooking,
        timeStart: props.editingBooking.timeStart.substring(0, 5),
        timeStop: props.editingBooking.timeStop.substring(0, 5)
      };
    } else {
      editForm.value = {
        masterId: props.masterId,
        clientId: null,
        petId: null,
        services: [],
        date: new Date().toISOString().split('T')[0],
        timeStart: "09:00",
        timeStop: "10:00"
      };
    }
  }
});

watch(() => editForm.value.clientId, async (newId) => {
  if (!newId) {
    currentClientPets.value = [];
    return;
  }
  const client = allClients.value.find(c => c.id === newId);
  if (client?.pets?.length) {
    const params = {};
    client.pets.forEach((petId, i) => { params[`id[${i}]`] = petId; });
    await fetchData('/api/v1/pets', params, data => {
      currentClientPets.value = data?.['hydra:member'] || data?.member || [];
    });
  } else {
    currentClientPets.value = [];
  }
});

const handleSave = async () => {
  const payload = {
    masterId: Number(props.masterId),
    services: editForm.value.services.map(s => Number(s)),
    date: editForm.value.date,
    timeStart: editForm.value.timeStart,
    timeStop: editForm.value.timeStop,
    petId: Number(editForm.value.petId),
    clientId: Number(editForm.value.clientId)
  };

  if (!props.editingBooking) {
    const data = await createBooking({
      url: '/api/v1/bookings',
      required: ['clientId', 'petId', 'services', 'date', 'timeStart', 'timeStop'],
      payload: payload,
      successMessage: "Бронювання створено!"
    });
    if (data) emit('saved', data);
  } else {
    const data = await updateBooking({
      id: `../bookings/${props.editingBooking.id}`,
      method: "PUT",
      payload: payload,
      successMessage: "Бронювання оновлено!"
    });
    if (data) emit('saved', data);
  }
};

const handleDelete = async () => {
  if (await remove(`/api/v1/bookings/${props.editingBooking.id}`)) {
    emit('deleted', props.editingBooking.id);
  }
};
</script>

<style lang="scss" scoped>
.pet-tag-edit {
  background: white;
  border: 1px solid #FF9229;
  padding: 6px 15px;
  border-radius: 12px;
  font-size: 14px;
  font-weight: 500;
  display: inline-flex;
  align-items: center;
  color: #333;
}

.form-control {
  height: 43px;
  border: 1px solid #e8e8e8;
  font-size: 14px;
  &:focus {
    border-color: #FF9229;
    box-shadow: none;
  }
}
</style>