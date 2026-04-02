<template>
  <div class="container py-5 mt-5">
    <div class="section-title text-start mb-5">
      <h1>Мій <span>Розклад роботи</span></h1>
    </div>

    <div v-if="loading && !schedule.length" class="text-center py-5">
      <div class="spinner-border text-orange" role="status"></div>
    </div>

    <div v-else class="hero-masters-wrapper container-wide">
      <div v-for="day in weekDays" :key="day.en" class="hero-master bg-white p-4 mb-4 shadow-sm position-relative">

        <div class="d-flex justify-content-between align-items-center mb-3">
          <h2 class="inter-20 mb-0" :class="{'text-muted': !getScheduleForDay(day.en)}">
            {{ day.ua }}
          </h2>
          <span v-if="getScheduleForDay(day.en)" class="badge bg-success-light text-success px-3 py-1 inter-12">Робочий</span>
          <span v-else class="badge bg-light text-muted px-3 py-1 inter-12">Вихідний</span>
        </div>

        <div class="border-top pt-4">
          <template v-if="getScheduleForDay(day.en)">
            <div v-if="editingId !== getScheduleForDay(day.en).id">
              <div class="inter-32 font-weight-bold mb-3">
                {{ formatTime(getScheduleForDay(day.en).start_time) }}
                <span class="inter-18 gray-text">—</span>
                {{ formatTime(getScheduleForDay(day.en).stop_time) }}
              </div>

              <div class="d-flex gap-3 mt-3">
                <button @click="startEdit(getScheduleForDay(day.en))" class="btn-action text-orange" title="Змінити час">✎</button>
                <button @click="deleteSchedule(getScheduleForDay(day.en).id)" class="btn-action text-danger" title="Зробити вихідним">✕</button>
              </div>
            </div>

            <div v-else class="edit-mode">
              <div class="d-flex align-items-center gap-2 mb-3">
                <input type="time" v-model="editData.start" class="form-control border-orange shadow-none">
                <span class="gray-text">—</span>
                <input type="time" v-model="editData.stop" class="form-control border-orange shadow-none">
              </div>
              <div class="d-flex gap-2">
                <button @click="saveSchedule(getScheduleForDay(day.en).id)"
                        class="btn-confirm flex-grow-1"
                        :disabled="updatingId === getScheduleForDay(day.en).id">
                  <span v-if="updatingId === getScheduleForDay(day.en).id" class="spinner-border spinner-border-sm"></span>
                  <span v-else>Зберегти ✓</span>
                </button>
                <button @click="editingId = null" class="btn-action text-secondary">✕</button>
              </div>
            </div>
          </template>

          <template v-else>
            <div v-if="addingDay !== day.en" class="text-center py-3">
              <button @click="startAdd(day.en)" class="orange-text border-0 bg-transparent inter-16 font-weight-bold">
                + Додати робочі години
              </button>
            </div>

            <div v-else class="edit-mode">
              <div class="d-flex align-items-center gap-2 mb-3">
                <input type="time" v-model="editData.start" class="form-control border-orange shadow-none">
                <input type="time" v-model="editData.stop" class="form-control border-orange shadow-none">
              </div>
              <div class="d-flex gap-2">
                <button @click="createSchedule(day.en)" class="btn-confirm flex-grow-1" :disabled="updating === 'new'">
                  Додати ✓
                </button>
                <button @click="addingDay = null" class="btn-action text-secondary">✕</button>
              </div>
            </div>
          </template>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useApiFetch } from "../useFetchResource";
import { useSubmit } from "../usePostResource";
import { useUpdate } from "../useUpdateResource";
import { useDelete } from "../useDeleteResource";

const { fetchData, loading } = useApiFetch();
const { submit, loading: creating } = useSubmit();
const { updateMaster: updateSchedule, loading: updatingPrice } = useUpdate();
const { remove: deleteResource } = useDelete();

const schedule = ref([]);
const masterId = ref(null);
const updating = ref(null);
const editingId = ref(null);
const addingDay = ref(null);
const updatingId = ref(null);

const editData = ref({ start: '10:00', stop: '18:00' });

const weekDays = [
  { en: 'Monday', ua: 'Понеділок' },
  { en: 'Tuesday', ua: 'Вівторок' },
  { en: 'Wednesday', ua: 'Середа' },
  { en: 'Thursday', ua: 'Четвер' },
  { en: 'Friday', ua: "П'ятниця" },
  { en: 'Saturday', ua: 'Субота' },
  { en: 'Sunday', ua: 'Неділя' }
];

const getScheduleForDay = (dayEn) => schedule.value.find(s => s.dayOfweek === dayEn);

const formatTime = (timeStr) => timeStr ? timeStr.substring(0, 5) : '--:--';

const fetchSchedule = async () => {
  if (!masterId.value) {
    const me = await fetchData('/api/v1/v1/master/me');
    masterId.value = me?.id;
  }
  const data = await fetchData('/api/v1/schedules', { 'master.id': masterId.value });
  if (data) schedule.value = data.member || data['hydra:member'] || [];
};

const startEdit = (item) => {
  editingId.value = item.id;
  editData.value = { start: formatTime(item.start_time), stop: formatTime(item.stop_time) };
};

const startAdd = (dayEn) => {
  addingDay.value = dayEn;
  editData.value = { start: '10:00', stop: '18:00' };
};

const saveSchedule = async (id) => {
  updatingId.value = id;
  const data = await updateSchedule({
    id: `../schedules/${id}`,
    method: 'PATCH',
    payload: {
      start_time: `${editData.value.start}:00`,
      stop_time: `${editData.value.stop}:00`
    },
    successMessage: 'Графік оновлено'
  });

  if (data) {
    editingId.value = null;
    await fetchSchedule();
  }
  updatingId.value = null;
};

const createSchedule = async (dayEn) => {
  updatingId.value = 'new';
  const data = await submit({
    url: '/api/v1/schedules',
    payload: {
      dayOfweek: dayEn,
      start_time: `${editData.value.start}:00`,
      stop_time: `${editData.value.stop}:00`,
      masterId: Number(masterId.value)
    },
    required: ['start_time', 'stop_time'],
    successMessage: 'Робочий день додано'
  });

  if (data) {
    addingDay.value = null;
    await fetchSchedule();
  }
  updatingId.value = null;
};

const deleteSchedule = async (id) => {
  const success = await deleteResource(`/api/v1/schedules/${id}`, 'Тепер це вихідний');
  if (success) {
    schedule.value = schedule.value.filter(s => s.id !== id);
  }
};

onMounted(fetchSchedule);
</script>

<style lang="scss" scoped>
.hero-masters-wrapper {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 25px;
  @media (max-width: 1200px) { grid-template-columns: repeat(2, 1fr); }
  @media (max-width: 768px) { grid-template-columns: 1fr; }
}

.hero-master {
  border-radius: 25px;
  border: 1px solid rgba(0,0,0,0.05);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12) !important;
}

.bg-success-light { background-color: #e8f5e9; }
.text-success { color: #2e7d32 !important; }

.inter-32 { font-size: 32px; font-weight: 700; }
.inter-20 { font-size: 20px; font-weight: 600; }
.inter-16 { font-size: 16px; }
.inter-12 { font-size: 12px; }

.border-orange { border: 2px solid #FF9229; border-radius: 12px; }
.orange-text { color: #FF9229; cursor: pointer; &:hover { opacity: 0.8; } }

.btn-action {
  background: none; border: none; font-size: 24px; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
  transition: transform 0.2s; &:hover { transform: scale(1.1); }
}

.btn-confirm {
  background: #FF9229; border: none; color: white; height: 45px; border-radius: 12px;
  font-weight: 600; transition: 0.3s; &:hover { background: #e67e17; }
}

.gray-text { color: #888; }
</style>