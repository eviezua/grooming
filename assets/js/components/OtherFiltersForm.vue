<template>
  <div v-if="visible" class="modal" @click.self="close">
    <div class="modal-dialog">
      <div class="modal-content rounded-4">

        <div class="modal-header">
          <h5 class="modal-title">Інші фільтри</h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>

        <div class="modal-body">

          <div class="mb-3">
            <VueMultiselect
                v-model="selectedServices"
                :options="serviceOptions"
                :multiple="true"
                :searchable="true"
                :internal-search="false"
                placeholder="Оберіть послуги"
                label="name"
                track-by="id"
                @search-change="onSearchService"
            />
          </div>

          <div class="mb-3">
            <VueMultiselect
                v-model="selectedSpecies"
                :options="speciesOptions"
                placeholder="Оберіть вид тварини"
                label="name"
                track-by="id"
            />
          </div>

          <div class="mb-3" v-if="selectedSpecies">
            <VueMultiselect
                v-model="selectedBreed"
                :options="breedOptions"
                :searchable="true"
                :internal-search="false"
                placeholder="Оберіть породу"
                label="name"
                track-by="id"
                @search-change="onSearchBreed"
            />
          </div>

          <div class="mb-3" v-if="selectedServices.length">
            <label class="form-label">Ваш бюджет (грн)</label>
            <input
                type="number"
                min="0"
                class="form-control"
                v-model.number="budget"
                placeholder="Наприклад: 500"
            >

          </div>

          <div class="mb-3" v-if="selectedServices.length">
            <p><strong>Середня вартість:</strong> {{ totalCost }} грн</p>
          </div>

          <div class="mb-3">
            <label class="form-label">Рейтинг від</label>
            <StarRating v-model="minRating" :max="5" />
          </div>

          <div class="mb-3">
            <label class="form-label">Рейтинг до</label>
            <StarRating v-model="maxRating" :max="5" />
          </div>

          <div class="mb-3">
            <label>Оберіть бажану дату або діапазон дат для запису</label>
            <DatePicker
                v-model="selectedDate"
                :inline="true"
                :range="true"
                :min-date="today"
                :start-date="today"
                :focus-start-date="true"
                format="yyyy-MM-dd"
                :enable-time-picker="false"
                :auto-apply="true"
            />
          </div>

          <div class="mb-3">
            <label>Оберіть бажаний час для запису</label>
            <DatePicker
                :inline="true"
                :hours-increment="1"
                :minutes-increment="15"
                v-model="selectedTime"
                :range="true"
                :time-picker="true"
                :auto-apply="true"
                :no-hours-overlay="true"
                :no-minutes-overlay="true"
            />
          </div>
          <div class="mb-3">
            <label class="form-label">Сортувати за</label>
            <select class="form-select" v-model="sortField">
              <option value="">--</option>
              <option value="avgRating">Рейтинг</option>
              <option value="totalPrice">Ціна</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Напрямок</label>
            <select class="form-select" v-model="sortDirection">
              <option value="null">--</option>
              <option value="asc">За зростанням</option>
              <option value="desc">За спаданням</option>
            </select>
          </div>

          <div class="mt-4">
            <button
                class="btn btn-primary w-100"
                @click="applyFilters"
            >
              Застосувати
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, nextTick, defineExpose, onMounted } from "vue";
import VueMultiselect from "vue-multiselect";
import DatePicker from "@vuepic/vue-datepicker";
import { useApiFetch } from "../useFetchResource";
import { useDebounce } from "../useDebounce";
import { useTotals } from "../useTotals";

export default {
  components: { VueMultiselect, DatePicker },
  setup(props, { emit }) {
    const visible = ref(false);

    const serviceOptions = ref([]);
    const selectedServices = ref([]);

    const speciesOptions = ref([]);
    const selectedSpecies = ref(null);

    const breedOptions = ref([]);
    const selectedBreed = ref(null);

    const budget = ref(null);

    const minRating = ref(null);
    const maxRating = ref(null);

    const today = new Date();
    const selectedDate = ref([]);

    const selectedTime = ref([
      { hours: 10, minutes: 30, seconds: 0 },
      { hours: 11, minutes: 30, seconds: 0 }
    ]);

    const sortField = ref("");
    const sortDirection = ref("");

    const { fetchData } = useApiFetch();
    const isShort = (s, min = 3) => s && s.length < min;

    const fetchServices = async (search = "") => {
      let params = { page: 1 };
      if (search) params.search = search;

      serviceOptions.value = await fetchData(
          "/api/v1/services",
          params,
          data => (data.member || []).map(s => ({
            id: s.id,
            name: s.name,
            cost: s.cost
          }))
      ) || [];
    };

    const onSearchService = useDebounce(search => {
      if (!isShort(search)) fetchServices(search);
    }, 300);

    const fetchBreeds = async (speciesName, search = "") => {
      if (!speciesName) {
        breedOptions.value = [];
        return;
      }
      if (isShort(search)) return;

      const params = { page: 1, spice: speciesName, search };
      breedOptions.value = await fetchData(
          "/api/v1/pets",
          params,
          d => (d.member || []).map(b => ({
            id: b.id,
            name: b.breed,
            cost_coficient: b.cost_coficient
          }))
      ) || [];
    };

    const onSearchBreed = useDebounce(search => {
      if (selectedSpecies.value) {
        fetchBreeds(selectedSpecies.value.name, search);
      }
    }, 300);

    const { totalCost } = useTotals(selectedServices, selectedBreed);

    watch(selectedSpecies, newVal => {
      fetchBreeds(newVal?.name || "");
      selectedBreed.value = null;
    });

    const applyFilters = () => {
      if (minRating.value && maxRating.value && minRating.value > maxRating.value) {
        [minRating.value, maxRating.value] = [maxRating.value, minRating.value]
      }
      const services = selectedServices.value.map(s => s.id)
      const coef = selectedBreed.value?.cost_coficient ?? 1
      const adjustedBudget = budget.value ? budget.value / coef : null

      const formatDate = (d) => {
        if (!d) return null;
        const year = d.getFullYear();
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
      }
      const formatTime = t =>
          `${String(t.hours).padStart(2,'0')}:${String(t.minutes).padStart(2,'0')}`

      const filters = {
        services: services,
        budget: adjustedBudget,
        minRating: minRating.value ?? 0,
        maxRating: maxRating.value ?? 5,
        date_from: selectedDate.value?.[0]
            ? formatDate(selectedDate.value[0])
            : null,

        date_to: selectedDate.value?.[1]
            ? formatDate(selectedDate.value[1])
            : selectedDate.value?.[0]
                ? formatDate(selectedDate.value[0])
                : null,

        start_time: selectedTime.value?.[0]
            ? formatTime(selectedTime.value[0])
            : null,

        end_time: selectedTime.value?.[1]
            ? formatTime(selectedTime.value[1])
            : null,
        sortField: sortField.value,
        sortDirection: sortDirection.value,
      }

      console.log(filters)

      emit('apply', filters)
      close()
    }

    const open = async () => {
      visible.value = true;

      await nextTick();
      fetchServices();
    };

    const close = () => {
      visible.value = false;
    };

    defineExpose({ open, close });

    onMounted(() => {
      const el = document.getElementById("app");
      if (el?.dataset?.species) {
        speciesOptions.value = JSON.parse(el.dataset.species)
            .map(s => ({ id: s, name: s }));
      }
    });

    return {
      visible,
      open,
      close,
      today,
      serviceOptions,
      selectedServices,
      speciesOptions,
      selectedSpecies,
      breedOptions,
      selectedBreed,
      selectedDate,
      selectedTime,
      totalCost,
      onSearchService,
      onSearchBreed,
      budget,
      applyFilters,
      minRating,
      maxRating,
      sortField,
      sortDirection
    };
  }
};
</script>