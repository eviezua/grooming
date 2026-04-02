<template>
  <div class="container py-5 mt-5">
    <div class="section-title text-start mb-5">
      <h1>Мої <span>Послуги та Ціни</span></h1>
    </div>

    <div v-if="loading && !myServices.length" class="text-center py-5">
      <div class="spinner-border text-orange" role="status"></div>
    </div>

    <div v-else>
      <div class="hero-masters-wrapper container-wide">

        <div @click="addNewService" class="hero-master add-service-card bg-light d-flex align-items-center justify-content-center mb-4 shadow-sm">
          <div class="text-center">
            <div class="plus-icon mb-2">+</div>
            <div class="inter-18 gray-text">Додати послугу</div>
          </div>
        </div>

        <div v-for="ms in myServices" :key="ms.id" class="hero-master bg-white p-4 mb-4 shadow-sm">
          <div class="mb-4">
            <h2 class="inter-20 mb-1">{{ serviceData[ms.serviceId]?.name || 'Завантаження...' }}</h2>
            <div class="inter-14 gray-text">
              Середня ціна: <span class="orange-text">{{ serviceData[ms.serviceId]?.avgCost || '...' }} ₴</span>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between border-top pt-4">
            <template v-if="editingId !== ms.id">
              <div class="inter-32 font-weight-bold">{{ ms.price }} <small class="inter-18">₴</small></div>
              <div class="d-flex gap-3">
                <button @click="startEdit(ms)" class="btn-action text-orange" title="Редагувати">✎</button>
                <button @click="deleteService(ms.id)" class="btn-action text-danger" title="Видалити">✕</button>
              </div>
            </template>

            <template v-else>
              <div class="d-flex align-items-center gap-2 w-100">
                <input
                    type="number"
                    v-model.number="editValue"
                    class="form-control border-orange shadow-none inter-24 px-3"
                    style="height: 50px; font-weight: 700;"
                >
                <button @click="savePrice(ms)" class="btn-confirm" :disabled="updating === ms.id">
                  <span v-if="updating === ms.id" class="spinner-border spinner-border-sm"></span>
                  <span v-else>✓</span>
                </button>
                <button @click="editingId = null" class="btn-action text-secondary" style="font-size: 26px;">✕</button>
              </div>
            </template>
          </div>
        </div>
      </div>

      <div v-if="myServices.length === 0 && !loading" class="text-center p-4">
        <h3 class="gray-text inter-18">У вас ще немає жодної активної послуги</h3>
      </div>

      <div class="text-center mt-5" v-if="hasMore">
        <a href="#" @click.prevent="loadMore" class="orange-but d-flex justify-content-center align-items-center text-white text-decoration-none inter-18 mx-auto" style="width: 182px; height: 73px;">
          Більше
        </a>
      </div>
    </div>
  </div>
  <MasterServiceModal
      v-model="showAddModal"
      :master-id="Number(masterId)"
      :existing-service-ids="myServices.map(ms => ms.serviceId)"
      @added="onServiceAdded"
  />
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useApiFetch } from "../useFetchResource";
import { useUpdate } from "../useUpdateResource";
import { useDelete } from "../useDeleteResource";

const { fetchData, loading } = useApiFetch();
const { updateMaster: updatePrice, loading: updatingPrice } = useUpdate();
const { remove: deleteResource } = useDelete();
const myServices = ref([]);
const serviceData = ref({});
const updating = ref(null);
const masterId = ref(null);

const page = ref(1);
const lastPage = ref(1);
const editingId = ref(null);
const editValue = ref(0);
const updatingId = ref(null);
const showAddModal = ref(false);

const startEdit = (ms) => {
  editingId.value = ms.id;
  editValue.value = ms.price;
};

const onServiceAdded = async () => {
  await fetchMyServices(true);
};

const addNewService = () => {
  showAddModal.value = true;
};

const savePrice = async (ms) => {
  updatingId.value = ms.id;

  const data = await updatePrice({
    id: `../masters_services/${ms.id}`,
    method: "PATCH",
    payload: { price: Number(editValue.value) },
    successMessage: "Ціну оновлено"
  });

  if (data) {
    ms.price = data.price;
    editingId.value = null;
  }
  updatingId.value = null;
};

const loadServiceDetails = async (items) => {
  const sIds = [...new Set(items.map(i => i.serviceId).filter(id => !serviceData.value[id]))];
  if (sIds.length > 0) {
    const params = {};
    sIds.forEach((id, index) => { params[`id[${index}]`] = id; });
    await fetchData('/api/v1/services', params, data => {
      const list = data?.member || data?.['hydra:member'] || [];
      list.forEach(s => {
        serviceData.value[s.id] = { name: s.name, avgCost: s.cost };
      });
    });
  }
};

const fetchMyServices = async (reset = false) => {
  if (!masterId.value) {
    const me = await fetchData('/api/v1/v1/master/me');
    masterId.value = me?.id;
  }

  if (masterId.value) {
    const data = await fetchData('/api/v1/masters_services', {
      'master.id': masterId.value,
      'order[price]': 'asc',
      'page': page.value
    });

    if (data) {
      const items = data.member || data['hydra:member'] || [];
      if (reset) myServices.value = items;
      else myServices.value.push(...items);

      const view = data['view'] || data['hydra:view'];
      if (view?.['last'] || view?.['hydra:last']) {
        const lastUrl = view['last'] || view['hydra:last'];
        const lastMatch = lastUrl.match(/page=(\d+)/);
        lastPage.value = lastMatch ? parseInt(lastMatch[1]) : page.value;
      } else {
        lastPage.value = page.value;
      }

      await loadServiceDetails(items);
    }
  }
};

const loadMore = () => {
  if (page.value < lastPage.value) {
    page.value++;
    fetchMyServices();
  }
};

const hasMore = computed(() => page.value < lastPage.value);

const deleteService = async (id) => {
  const success = await deleteResource(`/api/v1/masters_services/${id}`, "Послугу видалено");
  if (success) {
    myServices.value = myServices.value.filter(s => s.id !== id);
  }
};

onMounted(() => fetchMyServices(true));
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
  min-height: 220px;
  border-radius: 25px;
  border: 1px solid rgba(0,0,0,0.05);
  transition: all 0.3s ease;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.12) !important;

  &:hover {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.18) !important;
    transform: translateY(-2px);
  }
}

.add-service-card {
  cursor: pointer;
  border: 2px dashed #FF9229 !important;
  background-color: #fff9f4 !important;

  &:hover {
    background-color: #fff3e6 !important;
    .plus-icon { transform: scale(1.2); }
  }

  .plus-icon {
    font-size: 60px;
    line-height: 1;
    color: #FF9229;
    font-weight: 300;
    transition: transform 0.2s;
  }
}

.btn-action {
  background: none; border: none; font-size: 28px; cursor: pointer;
  line-height: 1; display: flex; align-items: center; justify-content: center;
  transition: transform 0.2s; &:hover { transform: scale(1.2); }
}

.btn-confirm {
  background: #FF9229; border: none; color: white; min-width: 50px; height: 50px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold;
}

.orange-but { border-radius: 20px; background-color: #FF9229; transition: 0.3s; &:hover { background-color: #e67e17; } }

.inter-32 { font-size: 32px; font-weight: 700; }
.inter-20 { font-size: 20px; font-weight: 600; }
.inter-18 { font-size: 18px; }
.orange-text { color: #FF9229; font-weight: 600; }
.gray-text { color: #888; }
</style>