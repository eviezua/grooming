<template>
  <div class="container py-5 mt-5">
    <div class="section-title text-start mb-5">
      <h1>Мої <span>Бронювання</span></h1>
    </div>

    <div v-if="loading && !bookings.length" class="text-center py-5">
      <div class="spinner-border text-orange" role="status"></div>
    </div>

    <div v-else>
      <div v-if="bookings.length === 0" class="white-but p-5 text-center mb-4">
        <h3 class="gray-text">Активних записів не знайдено</h3>
      </div>

      <div class="hero-masters-wrapper container-wide">
        <div @click="openCreateModal" class="hero-master add-booking-card bg-light d-flex align-items-center justify-content-center mb-4 shadow-sm">
          <div class="text-center">
            <div class="plus-icon mb-2">+</div>
            <div class="inter-18 gray-text">Додати новий запис</div>
          </div>
        </div>

        <div v-for="booking in bookings" :key="booking.id" class="hero-master bg-white position-relative p-4 mb-4 shadow-sm">
          <div class="d-flex justify-content-between align-items-start mb-3">
            <button
                v-if="booking.status !== 'Inactive'"
                @click="startEdit(booking)"
                class="btn-edit-square"
                title="Редагувати"
            >
              <span class="edit-icon">✎</span>
            </button>

            <div class="status-select-wrapper">
              <VueMultiselect
                  :model-value="statusOptions.find(o => o.id === booking.status) || { id: booking.status, name: booking.status }"
                  :options="statusOptions"
                  :disabled="booking.status === 'Inactive'"
                  label="name"
                  track-by="id"
                  @select="(option) => updateStatus(booking, option)"
                  :class="'status-custom-' + booking.status.toLowerCase()"
              />
            </div>
          </div>

          <h2 class="inter-24 mb-1">
            {{ booking.timeStart.substring(0, 5) }} — {{ booking.timeStop.substring(0, 5) }}
          </h2>
          <h3 class="inter-18 orange-text mb-4">{{ booking.date }}</h3>

          <div class="mb-4">
            <h3 class="gray-text inter-16 mb-2">
              <img src="/uploads/icons/customers.png" style="width: 18px" class="me-2">
              {{ clientNames[booking.clientId] || 'Завантаження...' }}
            </h3>
            <h3 class="gray-text inter-16">
              <img src="/uploads/icons/button_paw.png" style="width: 18px" class="me-2">
              Тварина: {{ petBreeds[booking.petId] || '...' }}
            </h3>
          </div>

          <div class="border-top pt-3">
            <h4 class="inter-16 mb-2">Послуги:</h4>
            <div v-for="sId in booking.services" :key="sId" class="inter-14 gray-text">
              • {{ serviceNames[sId] || 'Завантаження назви...' }}
            </div>
          </div>
        </div>
      </div>

      <div class="text-center mt-5" v-if="hasMore">
        <a href="#" @click.prevent="loadMore" class="orange-but d-flex justify-content-center align-items-center text-white text-decoration-none inter-18 mx-auto" style="width: 182px; height: 73px;">
          Більше
        </a>
      </div>
    </div>
  </div>

  <BookingModal
      v-model="showEditModal"
      :editing-booking="editingBooking"
      :master-id="Number(masterId)"
      :client-names="clientNames"
      :pet-breeds="petBreeds"
      @saved="onSaved"
      @deleted="onDeleted"
  />
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useApiFetch } from "../useFetchResource";
import { useUpdate } from "../useUpdateResource";
import VueMultiselect from "vue-multiselect";

const { fetchData, loading } = useApiFetch();
const { updateMaster: updateBooking } = useUpdate();

const bookings = ref([]);
const page = ref(1);
const lastPage = ref(1);
const masterId = ref(null);

const clientNames = ref({});
const petBreeds = ref({});
const serviceNames = ref({});

const showEditModal = ref(false);
const editingBooking = ref(null);

const statusOptions = [
  { id: 'Approved', name: 'Прийнято' },
  { id: 'Rejected', name: 'Відмінено' }
];

const loadRelatedNames = async (items) => {
  const newClientIds = [...new Set(items.map(b => b.clientId).filter(id => id && !clientNames.value[id]))];
  const newPetIds = [...new Set(items.map(b => b.petId).filter(id => id && !petBreeds.value[id]))];
  const newServiceIds = [...new Set(items.flatMap(b => b.services).filter(id => id && !serviceNames.value[id]))];

  const requests = [];

  if (newClientIds.length > 0) {
    const params = {};
    newClientIds.forEach((id, index) => {
      params[`id[${index}]`] = id;
    });

    requests.push(fetchData('/api/v1/clients', params, data => {
      const list = data?.member || data?.['hydra:member'] || [];
      list.forEach(c => {
        clientNames.value[c.id] = `${c.name} ${c.surname}`;
      });
      return data;
    }));
  }

  if (newPetIds.length > 0) {
    const params = {};
    newPetIds.forEach((id, index) => {
      params[`id[${index}]`] = id;
    });

    requests.push(fetchData('/api/v1/pets', params, data => {
      const list = data?.member || data?.['hydra:member'] || [];
      list.forEach(p => {
        petBreeds.value[p.id] = p.breed;
      });
      return data;
    }));
  }

  if (newServiceIds.length > 0) {
    const params = {};
    newServiceIds.forEach((id, index) => {
      params[`id[${index}]`] = id;
    });

    requests.push(fetchData('/api/v1/services', params, data => {
      const list = data?.member || data?.['hydra:member'] || [];
      list.forEach(s => {
        serviceNames.value[s.id] = s.name;
      });
      return data;
    }));
  }

  await Promise.all(requests);
};

const fetchBookings = async (reset = false) => {
  if (!masterId.value) {
    const me = await fetchData('/api/v1/v1/master/me');
    masterId.value = me?.id;
  }
  if (!masterId.value) return;

  const params = new URLSearchParams();
  params.append('page', page.value);
  params.append('id_master.id', masterId.value);

  const data = await fetchData('/api/v1/bookings', params, d => d);
  if (data) {
    const items = data.member || data['hydra:member'] || [];
    if (reset) bookings.value = items;
    else bookings.value.push(...items);

    if (data['view']?.last) {
      const lastUrl = new URL(data['view'].last, window.location.origin);
      lastPage.value = parseInt(lastUrl.searchParams.get('page'));
    } else {
      lastPage.value = page.value;
    }
    await loadRelatedNames(items);
  }
};

const openCreateModal = () => {
  editingBooking.value = null;
  showEditModal.value = true;
};

const startEdit = (booking) => {
  editingBooking.value = booking;
  showEditModal.value = true;
};

const onSaved = async (data) => {
  showEditModal.value = false;
  await fetchBookings(true);
};

const onDeleted = (id) => {
  bookings.value = bookings.value.filter(b => b.id !== id);
  showEditModal.value = false;
};

const updateStatus = async (booking, selectedOption) => {
  const data = await updateBooking({
    id: `../bookings/${booking.id}`,
    method: "PATCH",
    payload: { status: selectedOption.id },
    successMessage: `Статус змінено`
  });
  if (data) booking.status = data.status;
};

onMounted(() => fetchBookings(true));
</script>

<style lang="scss">
.hero-masters-wrapper {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 30px;
  @media (max-width: 1200px) { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 768px) { grid-template-columns: 1fr; }
}
.hero-master {
  border-radius: 25px;
  transition: all 0.3s ease;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
  &:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25) !important;
  }
}
.circle-block-sm {
  width: 45px; height: 45px;
  background: #fff3e6; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}
.orange-text { color: #FF9229; font-weight: 600; }

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

.add-booking-card {
  cursor: pointer;
  min-height: 380px;
  border: 2px dashed #FF9229 !important;
  background-color: #fff9f4 !important;
  border-radius: 25px;
  transition: all 0.3s ease;

  &:hover {
    background-color: #fff3e6 !important;
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15) !important;
    .plus-icon { transform: scale(1.2); }
  }

  .plus-icon {
    font-size: 60px;
    line-height: 1;
    color: #FF9229;
    font-weight: 300;
    transition: transform 0.2s;
  }

  .gray-text {
    color: #888;
    font-weight: 500;
  }
}
</style>