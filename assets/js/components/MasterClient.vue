<template>
  <div class="container py-5 mt-5">
    <div class="section-title text-start mb-5">
      <h1>{{ $t('My') }} <span>{{ $t('Clients') }}</span></h1>
    </div>

    <div v-if="loading && !clients.length" class="text-center py-5">
      <div class="spinner-border text-orange" role="status"></div>
    </div>

    <div v-else>
      <div v-if="clients.length === 0" class="white-but p-5 text-center">
        <h3 class="gray-text">{{ $t('You have no regular customers') }}</h3>
      </div>

      <div class="hero-masters-wrapper container-wide">
        <div v-for="client in clients" :key="client.id" class="hero-master bg-white position-relative p-4 mb-4 shadow-sm">

          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex align-items-center">
              <div class="circle-block-sm me-3">
                <img src="/uploads/icons/customers.png" style="width: 25px">
              </div>
              <div>
                <h2 class="inter-24 mb-0">{{ client.name }} {{ client.surname }}</h2>
                <span class="inter-14 orange-text">{{ $t('Regular client') }}</span>
              </div>
            </div>
          </div>

          <div class="mb-4">
            <div class="inter-16 mb-2">
              <i class="bi bi-envelope me-2"></i> {{ client.email }}
            </div>
            <div class="inter-16 mb-2 font-weight-bold">
              <i class="bi bi-telephone me-2"></i> {{ client.phone }}
            </div>
          </div>

          <div class="row g-2 mb-4">
            <div class="col-6">
              <div class="stat-box p-2 text-center border rounded">
                <div class="inter-18 font-weight-bold">{{ client.bookings?.length || 0 }}</div>
                <div class="inter-12 gray-text">{{ $t('Visits') }}</div>
              </div>
            </div>
            <div class="col-6">
              <div class="stat-box p-2 text-center border rounded">
                <div class="inter-18 font-weight-bold">{{ client.pets?.length || 0 }}</div>
                <div class="inter-12 gray-text">{{ $t('Pets') }}</div>
              </div>
            </div>
          </div>

          <div class="border-top pt-3">
            <h4 class="inter-16 mb-2">{{ $t('Client`s pets:') }}</h4>
            <div v-if="client.pets?.length" class="d-flex flex-wrap gap-2">
              <div v-for="pId in client.pets" :key="pId">
                <span class="badge bg-light text-dark border px-3 py-2 inter-14" style="border-radius: 12px; font-weight: 500;">
                  <i class="bi bi-paw me-1 opacity-50"></i>
                  {{ petBreeds[pId] || $t('Loading...') }}
                </span>
              </div>
            </div>
            <div v-else class="inter-14 italic gray-text">{{ $t('No data about pets') }}</div>
          </div>
        </div>
      </div>

      <div class="text-center mt-5" v-if="hasMore">
        <a href="#" @click.prevent="loadMore" class="orange-but d-flex justify-content-center align-items-center text-white text-decoration-none inter-18 mx-auto" style="width: 182px; height: 73px;">
          {{ $t('More') }}
        </a>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import { useApiFetch } from "../useFetchResource";

const { fetchData, loading } = useApiFetch();
const clients = ref([]);
const petBreeds = ref({});
const page = ref(1);
const lastPage = ref(1);

const loadPetNames = async (items) => {
  const newPetIds = [...new Set(items.flatMap(c => c.pets).filter(id => id && !petBreeds.value[id]))];

  if (newPetIds.length > 0) {
    const params = {};
    newPetIds.forEach((id, index) => {
      params[`id[${index}]`] = id;
    });

    await fetchData('/api/v1/pets', params, data => {
      const list = data?.member || data?.['hydra:member'] || [];
      list.forEach(p => {
        const spiceLabel = p.spice ? ` ${p.spice.toLowerCase()}` : '';
        petBreeds.value[p.id] = `${p.breed}${spiceLabel}`;
      });
    });
  }
};

const fetchClients = async (reset = false) => {
  const params = { page: page.value };
  const data = await fetchData('/api/v1/clients', params, d => d);

  if (data) {
    const items = data.member || data['hydra:member'] || [];
    if (reset) clients.value = items;
    else clients.value.push(...items);

    if (data['view']?.last) {
      const lastMatch = data['view'].last.match(/page=(\d+)/);
      lastPage.value = lastMatch ? parseInt(lastMatch[1]) : page.value;
    }

    await loadPetNames(items);
  }
};

const loadMore = () => {
  if (page.value < lastPage.value) {
    page.value++;
    fetchClients();
  }
};

const hasMore = computed(() => page.value < lastPage.value);

onMounted(() => fetchClients(true));
</script>

<style lang="scss" scoped>
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
  width: 50px; height: 50px;
  background: #fff3e6; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}

.stat-box {
  background-color: #fdfdfd;
  transition: background 0.2s;
  &:hover { background-color: #fff3e6; }
}

.orange-text { color: #FF9229; font-weight: 600; }
.gray-text { color: #888; }
.italic { font-style: italic; }
</style>