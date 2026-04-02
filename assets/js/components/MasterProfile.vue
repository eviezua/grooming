<template>
  <div v-if="loading && !master" class="text-center py-5">
    <div class="spinner-border text-orange" role="status"></div>
  </div>

  <div v-else-if="master" class="profile-layout text-start">
    <header class="profile-hero p-5 text-white">
      <div class="container d-flex align-items-center">
        <div class="circle-block me-5 shadow-lg position-relative avatar-edit" @click="$refs.fileInput.click()">
          <img :src="master.photo ? `/uploads/photos/${master.photo}` : '/uploads/icons/profile.png'" alt="MasterPhoto">
          <div class="avatar-overlay"><span>Змінити фото</span></div>
          <input type="file" ref="fileInput" class="d-none" accept="image/*" @change="onFileChange">
        </div>

        <div class="hero-text flex-grow-1">
          <div v-if="editingField !== 'fullName'" class="d-flex align-items-center gap-3">
            <h1 class="groomify-title mb-1 text-white">Я, {{ master.name }} {{ master.surname }},</h1>
            <button @click="startEdit('fullName', {name: master.name, surname: master.surname})" class="btn-edit-white">✎</button>
          </div>
          <div v-else class="d-flex align-items-center gap-2 mb-2 scale-in">
            <input v-model="editValue.name" class="form-control hero-input" placeholder="Ім'я">
            <input v-model="editValue.surname" class="form-control hero-input" placeholder="Прізвище">
            <button @click="saveField('fullName')" class="btn-confirm-white" :disabled="updating">✓</button>
            <button @click="cancelEdit" class="btn-cancel-white">✕</button>
          </div>
          <h2 class="section-title mt-0"><span class="text-white">І я крутий грумер!</span></h2>
        </div>
      </div>
    </header>

    <main class="container py-5 mt-5">
      <div class="row g-5">
        <div class="col-lg-4 d-flex flex-column gap-5">
          <div class="white-but p-4">
            <h3 class="inter-22 mb-3">Рейтинг майстра</h3>
            <div class="d-flex justify-content-start">
              <StarRating :model-value="master.avgRating" :readonly="true" />
            </div>
            <div class="inter-18 gray-text mt-2">На основі відгуків клієнтів</div>
          </div>
          <button @click="isPasswordModalOpen = true" class="orange-but w-100 text-white hover-glow">
            Змінити пароль
          </button>
        </div>

        <div class="col-lg-8 d-flex flex-column gap-5">
          <div class="white-but p-4">
            <h3 class="inter-22 mb-4">Локація та контакти</h3>
            <div class="row">
              <div class="col-md-6 mb-3">
                <div class="inter-18 gray-text d-flex align-items-center gap-2">
                  Email
                  <button v-if="editingField !== 'email'" @click="startEdit('email', master.email)" class="btn-edit-small">✎</button>
                </div>
                <div v-if="editingField !== 'email'" class="inter-20">{{ master.email }}</div>
                <div v-else class="d-flex align-items-center gap-2 mt-1">
                  <input v-model="editValue" class="form-control edit-input" type="email" autofocus>
                  <button @click="saveField('email')" class="btn-confirm-small" :disabled="updating">✓</button>
                  <button @click="cancelEdit" class="btn-cancel-small">✕</button>
                </div>

                <div class="inter-18 gray-text d-flex align-items-center gap-2 mt-3">
                  Телефон
                  <button v-if="editingField !== 'phone'" @click="startEdit('phone', master.phone)" class="btn-edit-small">✎</button>
                </div>
                <div v-if="editingField !== 'phone'" class="inter-20">{{ master.phone || 'Не вказано' }}</div>
                <div v-else class="d-flex align-items-center gap-2 mt-1">
                  <input v-model="editValue" class="form-control edit-input" autofocus>
                  <button @click="saveField('phone')" class="btn-confirm-small" :disabled="updating">✓</button>
                  <button @click="cancelEdit" class="btn-cancel-small">✕</button>
                </div>
              </div>

              <div class="col-md-12 mb-3">
                <div class="inter-18 gray-text d-flex align-items-center gap-2 mb-2">
                  Локація
                  <button v-if="editingField !== 'location'" @click="startEdit('location')" class="btn-edit-small">✎</button>
                </div>
                <div v-if="editingField !== 'location'" class="inter-20">
                  {{ cityName || 'Місто не вказано' }}{{ districtName ? `, р-н ${districtName}` : '' }}
                </div>
                  <LocationEdit
                      v-else
                      :city-options="cityOptions"
                      :initial-city="selectedCity"
                      :initial-district="selectedDistrict"
                      :updating="updating"
                      :fetch-data="fetchData"
                      @save="onLocationSave"
                      @cancel="cancelEdit"
                  />
              </div>
            </div>
          </div>

          <div class="white-but p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
              <h3 class="inter-22">Моя спеціалізація</h3>
              <button @click="openPetsModal" class="btn-edit-small">Змінити ✎</button>
            </div>
            <div v-if="Object.keys(groupedPets).length === 0" class="text-center py-3 gray-text">Ви ще не обрали тварин</div>
            <div v-else class="d-flex flex-column gap-4">
              <div v-for="(breeds, spice) in groupedPets" :key="spice">
                <div class="inter-16 orange-text fw-bold text-uppercase mb-3">{{ spiceLabels[spice] || spice }}</div>
                <div class="d-flex flex-wrap gap-2 ps-2">
                  <span v-for="breed in breeds" :key="breed" class="pet-tag shadow-sm">{{ breed }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </main>
  </div>

  <PhotoCropModal
      v-if="isPhotoModalOpen"
      :image-to-crop="imageToCrop"
      :master-id="master?.id"
      :updating="updating"
      @close="isPhotoModalOpen = false"
      @success="onPhotoSuccess"
  />

  <EditPasswordModal
      v-if="isPasswordModalOpen"
      :updating="updating"
      @close="isPasswordModalOpen = false"
      @save="onPasswordSave"
  />

  <PetsSpecializationModal
      v-if="isPetsModalOpen"
      :initial-pets="selectedPetsForEdit"
      :spice-labels="spiceLabels"
      :species-options="speciesOptions"
      :updating="updating"
      :fetch-data="fetchData"
      @close="isPetsModalOpen = false"
      @save="onPetsSave"
  />
</template>

<script setup>
import { ref, onMounted, watch } from 'vue';
import { useApiFetch } from "../useFetchResource";
import { useUpdate } from "../useUpdateResource";
import StarRating from "./StarRating.vue";

const { fetchData, loading } = useApiFetch();
const { updateMaster, loading: updating } = useUpdate();

const master = ref(null);
const cityName = ref('');
const districtName = ref('');
const groupedPets = ref({});
const editingField = ref(null);
const editValue = ref('');

const cityOptions = ref([]);
const districtOptions = ref([]);
const selectedCity = ref(null);
const selectedDistrict = ref(null);

const isPhotoModalOpen = ref(false);
const imageToCrop = ref(null);
const isPasswordModalOpen = ref(false);
const isPetsModalOpen = ref(false);
const selectedPetsForEdit = ref([]);
const speciesOptions = ref([]);

const spiceLabels = { 'Cat': 'Коти', 'Dog': 'Собаки', 'Rodent': 'Гризуни', 'Bird': 'Птахи', 'Rabbit': 'Кролики' };

const loadProfile = async () => {
  const meData = await fetchData('/api/v1/v1/master/me');
  if (!meData?.id) return;
  const profile = await fetchData(`/api/v1/masters/${meData.id}`);
  if (profile) {
    master.value = profile;
    if (profile.cityId) {
      const city = await fetchData(`/api/v1/cities/${profile.cityId}`);
      selectedCity.value = { id: city.id, name: city.city };
      cityName.value = city.city;
    }
    if (profile.districtId) {
      const district = await fetchData(`/api/v1/districts/${profile.districtId}`);
      selectedDistrict.value = { id: district.id, name: district.name };
      districtName.value = district.name;
    }
    await loadGroupedPets(profile.petsId);
  }
};

const loadGroupedPets = async (ids) => {
  if (!ids?.length) { groupedPets.value = {}; return; }
  const params = {};
  ids.forEach((id, i) => params[`id[${i}]`] = id);
  const data = await fetchData('/api/v1/pets', params);
  const items = data?.member || data?.['hydra:member'] || [];
  const groups = {};
  items.forEach(p => {
    if (!groups[p.spice]) groups[p.spice] = new Set();
    groups[p.spice].add(p.breed);
  });
  const result = {};
  Object.keys(groups).forEach(k => result[k] = Array.from(groups[k]).sort());
  groupedPets.value = result;
};

const startEdit = (f, v) => { editingField.value = f; editValue.value = v; };
const cancelEdit = () => { editingField.value = null; editValue.value = ''; };

const saveField = async (field) => {
  const payload = field === 'fullName'
      ? { name: editValue.value.name, surname: editValue.value.surname }
      : { [field]: editValue.value };

  const result = await updateMaster({ id: master.value.id, payload, method: 'PATCH' });

  if (result) {
    if (field === 'email') {
      setTimeout(async () => {
        Object.assign(master.value, payload);

        try {
        } catch (e) {
          console.error("Все ще викидає? Перевір вкладку Network!");
        }
        cancelEdit();
      }, 500);
    } else {
      Object.assign(master.value, payload);
      cancelEdit();
    }
  }
};

const onLocationSave = async ({ city, district }) => {
  const payload = { cityId: city.id, districtId: district.id };

  if (await updateMaster({ id: master.value.id, payload, method: 'PATCH' })) {
    master.value.cityId = payload.cityId;
    master.value.districtId = payload.districtId;

    selectedCity.value = city;
    selectedDistrict.value = district;
    cityName.value = city.name;
    districtName.value = district.name;

    cancelEdit();
  }
};

const onFileChange = (e) => {
  const file = e.target.files[0];
  if (file) {
    const reader = new FileReader();
    reader.onload = (ev) => { imageToCrop.value = ev.target.result; isPhotoModalOpen.value = true; };
    reader.readAsDataURL(file);
  }
};
const onPhotoSuccess = (photoName) => {
  master.value.photo = photoName;
  isPhotoModalOpen.value = false;
};

const onPasswordSave = async (form, setError) => {
  try {
    const result = await updateMaster({
      id: master.value.id,
      payload: {
        password: form.newPassword,
        oldPassword: form.oldPassword
      },
      method: 'PATCH'
    });

    if (result) {
      isPasswordModalOpen.value = false;
    } else {
      setError('Неправильний поточний пароль');
    }
  } catch (error) {
    const msg = error.response?.data?.violations?.[0]?.message
        || 'Поточний пароль введено неправильно';
    setError(msg);
  }
};

const openPetsModal = async () => {
  selectedPetsForEdit.value = [];

  isPetsModalOpen.value = true;

  if (master.value?.petsId?.length) {
    const params = {};
    master.value.petsId.forEach((id, i) => params[`id[${i}]`] = id);

    const data = await fetchData('/api/v1/pets', params);
    const items = data?.['hydra:member'] || data?.member || [];

    selectedPetsForEdit.value = items.map(p => ({
      id: p.id,
      name: p.breed,
      spice: p.spice
    }));
  }
};
const onPetsSave = async (ids) => {
  if (await updateMaster({ id: master.value.id, payload: { petsId: ids }, method: 'PATCH' })) {
    master.value.petsId = ids;
    await loadGroupedPets(ids);
    isPetsModalOpen.value = false;
  }
};

watch(selectedCity, async (newCity) => {
  if (!newCity) { districtOptions.value = []; selectedDistrict.value = null; return; }
  const data = await fetchData('/api/v1/districts', { 'city.id[]': newCity.id }, j => (j['hydra:member'] || j['member'] || []).map(d => ({ id: d.id, name: d.name })));
  if (data) districtOptions.value = data;
});

onMounted(async () => {
  await loadProfile();
  const cities = await fetchData('/api/v1/cities', {}, j => (j['hydra:member'] || []).map(i => ({ id: i.id, name: i.city })));
  if (cities) cityOptions.value = cities;
  const el = document.getElementById("app");
  speciesOptions.value = el?.dataset?.species ? JSON.parse(el.dataset.species).map(s => ({ id: s, name: s })) : Object.keys(spiceLabels).map(k => ({ id: k, name: spiceLabels[k] }));
});
</script>

<style lang="scss">
.profile-hero {
  background-color: #FF9229;
  min-height: 300px;
  display: flex;
  align-items: center;
  padding: 3rem 1rem;

  .container {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    gap: 1.5rem;

    @media (min-width: 992px) {
      flex-direction: row;
      text-align: left;
      align-items: center;
      gap: 3rem;
    }
  }
}

.circle-block {
  width: 140px;
  height: 140px;
  flex-shrink: 0;
  border-radius: 50%;
  border: 4px solid rgba(255,255,255,0.3);
  overflow: hidden;
  position: relative;

  @media (min-width: 992px) {
    width: 200px;
    height: 200px;
    border-width: 6px;
  }

  img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.hero-text {
  width: 100%;

  .groomify-title {
    font-size: 1.8rem;
    @media (min-width: 992px) {
      font-size: 2.5rem;
    }
  }

  .section-title span {
    display: block;
    word-wrap: break-word;
  }
}

.btn-edit-white, .btn-confirm-white, .btn-cancel-white {
  flex-shrink: 0;
}

.white-but { background: #fff; border-radius: 25px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
.pet-tag { background: #fff; border: 1px solid rgba(255,146,41,0.3); color: #444; padding: 6px 16px; border-radius: 12px; font-size: 14px; }
.orange-but { background: #FF9229; border: none; border-radius: 12px; font-weight: bold; transition: 0.3s; &:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(255,146,41,0.4); } }
.btn-edit-white { background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; }
.hero-input { background: rgba(255,255,255,0.2) !important; border: 1px solid rgba(255,255,255,0.5) !important; color: white !important; border-radius: 12px; }
.avatar-edit { cursor: pointer; transition: 0.3s; &:hover .avatar-overlay { opacity: 1; } }
.avatar-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); color: white; display: flex; align-items: center; justify-content: center; opacity: 0; transition: 0.3s; font-weight: bold; font-size: 14px; }
</style>