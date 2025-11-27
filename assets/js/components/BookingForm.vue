<template>
  <div v-if="visible" class="modal" @click.self="close">
    <div class="modal-dialog">
      <div class="modal-content rounded-4">
        <div class="modal-header">
          <h5 class="modal-title">Бронювання</h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>
        <div class="modal-body">
          <div class="mb-3" v-if="groomer">
            <strong>Майстер: </strong>{{ groomer.name }} {{ groomer.surname }}
          </div>
          <form-wizard id="bookingForm" @on-complete="onComplete" color="#FF9229" :use-validation="false">
            <tab-content title="Step 1">
              <div class="mb-3">
                <VueMultiselect
                    v-model="selectedServices"
                    :options="serviceOptions"
                    :multiple="true"
                    :taggable="true"
                    :internal-search="false"
                    :searchable="true"
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
                    :multiple="false"
                    placeholder="Оберіть вид тварини"
                    label="name"
                    track-by="id"
                />
              </div>
              <div class="mb-3">
                <VueMultiselect
                    v-if="selectedSpecies"
                    v-model="selectedBreed"
                    :options="breedOptions"
                    :multiple="false"
                    :internal-search="false"
                    :searchable="true"
                    placeholder="Оберіть породу тварини"
                    label="name"
                    track-by="id"
                    @search-change="onSearchBreed"
                />
              </div>
              <div class="mb-3" v-if="selectedServices.length">
                <p><strong>Загальна вартість:</strong> {{ totalCost }} грн</p>
                <p><strong>Вартість з урахуванням майстра:</strong> {{ totalMasterCost }} грн</p>
              </div>
            </tab-content>

            <tab-content title="Step 2">
              <div class="mb-3" v-if="schedules.length">
                <strong>Графік майстра:</strong>
                <ul>
                  <li v-for="s in schedules" :key="s.id">
                    {{ s.dayOfweek }}: {{ s.start_time }} - {{ s.stop_time }}
                  </li>
                </ul>
              </div>
              <div class="mb-3">
                <BookingCalendar :schedules="schedules"
                                 :bookings="bookings"
                                 :onDateChange="fetchBookings"
                                 :slotStep="totalMinutes"
                                 v-model:selectedDate="selectedDate"
                                 v-model:selectedTime="selectedTime"/>
              </div>
            </tab-content>

            <tab-content title="Step 3">
              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" v-model="showFullForm" id="toggleFullForm">
                <label class="form-check-label" for="toggleFullForm">
                  Вперше у нас?
                </label>
              </div>
              <div class="mb-3">
                <label class="form-label">Email</label>
                <input v-model="client.email" type="email" class="form-control" placeholder="user@example.com" required>
              </div>
              <div v-if="showFullForm">
                <div class="mb-3">
                  <label class="form-label">Ім’я</label>
                  <input v-model="client.name" type="text" class="form-control" placeholder="Ваше ім’я" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Прізвище</label>
                  <input v-model="client.surname" type="text" class="form-control" placeholder="Ваше прізвище" required>
                </div>
                <div class="mb-3">
                  <label class="form-label">Телефон</label>
                  <input v-model="client.phone" type="tel" class="form-control" placeholder="+380..." required>
                </div>
              </div>
            </tab-content>
          </form-wizard>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, defineExpose, nextTick, onMounted} from "vue";
import BookingCalendar from "./BookingCalendar.vue";
import { useApiFetch } from "../useFetchResource";
import { useWizardComplete } from "../useWizardComplete";
import { useSubmit } from "../usePostResource";
import { useDebounce } from "../useDebounce";
import { useTotals } from '../useTotals';

export default {
  components: { BookingCalendar},
  props: { groomer: Object },
  setup(props) {
    const visible = ref(false);

    const serviceOptions = ref([]);
    const selectedServices = ref([]);

    const speciesOptions = ref([]);
    const selectedSpecies = ref(null);

    const breedOptions = ref([]);
    const selectedBreed = ref(null);

    const schedules = ref([]);
    const bookings = ref([]);
    const client = ref({
      id: null,
      name: '',
      surname: '',
      email: '',
      phone: ''
    });

    const selectedDate = ref(null)
    const selectedTime = ref(null)

    const showFullForm = ref(false);
    const { fetchData } = useApiFetch();
    const { submit, loading } = useSubmit();
    const isSearchTooShort = (s, min = 3) => s && s.length < min;

    const fetchServices = async (search = '') => {
      if (!props.groomer?.servicesId?.length || isSearchTooShort(search)) {
        serviceOptions.value = [];
        return;
      }

      const params = { id_masters: props.groomer.id };
      params['id[]'] = props.groomer.servicesId;
      params.search = search;

      const baseServices = await fetchData('/api/v1/services', params, data =>
          (data.member || []).map(s => ({ ...s, master_price: null }))
      );

      if (!baseServices) {
        serviceOptions.value = [];
        return;
      }

      await Promise.all(
          baseServices.map(async service => {
            const priceData = await fetchData(
                '/api/v1/masters_services',
                { page: 1, 'service.id': service.id, 'master.id': props.groomer.id },
                d => d.member?.[0] || null
            );
            service.master_price = priceData?.price ?? service.cost;
          })
      );

      serviceOptions.value = baseServices;
    };

    const onSearchService = useDebounce((search) => {
      if (!isSearchTooShort(search)) fetchServices(search);
    }, 400);

    const fetchBreeds = async (speciesName, search = '') => {
      if (!speciesName) {
        breedOptions.value = [];
        selectedBreed.value = null;
        return;
      }
      if (isSearchTooShort(search)) return;

      const params = { page: 1, spice: speciesName };
      params.search = search;

      const data = await fetchData('/api/v1/pets', params, d =>
          (d.member || []).map(pet => ({ id: pet.id, name: pet.breed, cost_coficient: pet.cost_coficient }))
      );

      breedOptions.value = data || [];
    };

    const onSearchBreed = useDebounce(search => fetchBreeds(selectedSpecies.value?.name, search), 400);

    const fetchSchedules = async () => {
      if (!props.groomer?.id) return;
      const data = await fetchData('/api/v1/schedules', { page: 1, 'master.id': props.groomer.id });
      schedules.value = data.member || [];
    };

    const fetchBookings = async (date) => {
      if (!props.groomer?.id || !date) return;
      const formattedDate = date.toISOString().split('T')[0];
      const data = await fetchData('/api/v1/bookings', { page: 1, 'date[after]': formattedDate, 'id_master.id': props.groomer.id });
      bookings.value = data.member || [];
    };

    const fetchClientByEmail = async (email) => {
      if (!email) {
        client.value.id = null;
        return;
      }

      const data = await fetchData('/api/v1/v1/clients/find-by-email', { email: email }, data => data, "Клієнта знайдено!" );

      if (data) {
        showFullForm.value = false;
        client.value.id = data.id;
      } else {
        client.value.id = null;
      }
    };

    function getTimeStop(timeStart) {
      if (!timeStart) return null;
      const [h, m] = timeStart.split(':').map(Number);
      const date = new Date();
      date.setHours(h, m);
      date.setMinutes(date.getMinutes() + totalMinutes.value);
      return `${date.getHours().toString().padStart(2,'0')}:${date.getMinutes().toString().padStart(2,'0')}`;
    }

    const submitBooking = async () => {
      const formatTime = (time) => {
        if (!time) return null;
        return time.length === 5 ? `${time}:00` : time;
      }

      const baseData = {
        masterId: props.groomer.id,
        services: selectedServices.value.map(s => s.id),
        date: selectedDate.value?.toISOString().split("T")[0],
        timeStart: formatTime(selectedTime.value),
        timeStop: formatTime(getTimeStop(selectedTime.value)),
        petId: selectedBreed.value?.id
      };

      const payload = { ...baseData };
      let url = "/api/v1/bookings";

      if (client.value.id) {
        payload.clientId = client.value.id;
      } else {
        payload.clientName = client.value.name;
        payload.clientSurname = client.value.surname;
        payload.clientEmail = client.value.email;
        payload.clientPhone = client.value.phone;
        url = "/api/v1/bookings/with-new-client";
      }

      await submit({
        url,
        payload: payload || {},
        required: ["masterId", "services", "date", "timeStart", "timeStop", "petId"],
        successMessage: "Бронювання створено!",
        onSuccess: () => close()
      });
    };

    const { totalCost, totalMasterCost, totalMinutes, totalTime } = useTotals(selectedServices, selectedBreed);

    const { runWizardComplete } = useWizardComplete();
    const onComplete = () =>{
      runWizardComplete(submitBooking);
    }

    function resetForm() {
      selectedServices.value = [];
      selectedSpecies.value = null;
      selectedBreed.value = null;
      breedOptions.value = [];
      schedules.value = [];
      selectedDate.value = null;
      selectedTime.value = null;
    }

    const open = async () => {
      await nextTick();
      visible.value = true;
      await fetchServices();
      await fetchSchedules();
      await fetchBookings(new Date());
    };

    const close = () => {
      visible.value = false;
      resetForm();
    };

    defineExpose({ open, close });

    watch(selectedSpecies, newSpecies => fetchBreeds(newSpecies?.name || ''));

    watch(() => props.groomer, g => { if (!g) serviceOptions.value = [] });

    watch(() => client.value.email, useDebounce((email) => fetchClientByEmail(email), 400));

    watch(showFullForm, newValue => {
      if (!newValue) {
        client.value.name = '';
        client.value.surname = '';
        client.value.phone = '';
      }
    });

    onMounted(() => {
      const el = document.getElementById("app");
      if (el?.dataset?.species) {
        const species = JSON.parse(el.dataset.species);
        speciesOptions.value = species.map((s) => ({ id: s, name: s }));
      }
    });

    return { visible, open, close, speciesOptions, selectedSpecies, serviceOptions, selectedServices, breedOptions, selectedBreed, schedules, bookings, client, showFullForm, selectedDate, selectedTime, submitBooking, loading, totalCost, totalTime, totalMasterCost, totalMinutes, onSearchBreed, onSearchService, onComplete, fetchBookings };
  }
};
</script>