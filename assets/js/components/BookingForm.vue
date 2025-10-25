<template>
  <div v-if="visible" class="modal" @click.self="close">
    <div class="modal-dialog">
      <div class="modal-content rounded-4">
        <div class="modal-header">
          <h5 class="modal-title">Бронювання</h5>
          <button type="button" class="btn-close" @click="close"></button>
        </div>

        <div class="modal-body">
          <form id="bookingForm">
            <div class="mb-3" v-if="groomer">
              <strong>Майстер: </strong>{{ groomer.name }} {{ groomer.surname }}
            </div>

            <div class="mb-3">
                <VueMultiselect
                    v-model="selectedServices"
                    :options="serviceOptions"
                    :multiple="true"
                    :taggable="true"
                    placeholder="Оберіть послуги"
                    label="name"
                    track-by="id"
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
                  v-if="breedOptions.length"
                  v-model="selectedBreed"
                  :options="breedOptions"
                  :multiple="false"
                  placeholder="Оберіть породу тварини"
                  label="name"
                  track-by="id"
              />
            </div>
            <div class="mb-3" v-if="schedules.length">
              <strong>Графік майстра:</strong>
              <ul>
                <li v-for="s in schedules" :key="s.id">
                  {{ s.dayOfweek }}: {{ s.start_time }} - {{ s.stop_time }}
                </li>
              </ul>
            </div>
            <div class="mb-3">
              <BookingCalendar :schedules="schedules" :bookings="bookings" :onDateChange="fetchBookings" v-model:selectedDate="selectedDate"
                               v-model:selectedTime="selectedTime"/>
            </div>

            <div class="form-check mb-3">
              <input class="form-check-input" type="checkbox" v-model="showFullForm" id="toggleFullForm">
              <label class="form-check-label" for="toggleFullForm">
                Вперше у нас?
              </label>
            </div>

            <div class="mb-3">
              <label class="form-label">Email</label>
              <input v-model="client.email" type="email" class="form-control" placeholder="user@example.com" required>
              <div v-if="emailError" class="text-danger mt-1">{{ emailError }}</div>
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
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="close">Скасувати</button>
          <button type="button" class="btn btn-primary" @click="submitBooking">
            Підтвердити
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, watch, defineExpose, nextTick, onMounted } from "vue";
import BookingCalendar from "./BookingCalendar.vue";
import VueMultiselect from 'vue-multiselect';

export default {
  components: { BookingCalendar, VueMultiselect },
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

    const emailError = ref('');
    const showFullForm = ref(false);

    const fetchServices = async () => {
      if (!props.groomer?.servicesId?.length) {
        serviceOptions.value = [];
        return;
      }
      const params = new URLSearchParams();
      props.groomer.servicesId.forEach((id) => params.append("id[]", id));
      params.append("id_masters", props.groomer.id);

      const res = await fetch(`/api/v1/services?${params.toString()}`);
      const data = await res.json();
      serviceOptions.value = data.member.map((s) => ({ id: s.id, name: s.name })) || [];
    };

    const fetchBreeds = async (speciesName) => {
      if (!speciesName) {
        breedOptions.value = [];
        selectedBreed.value = null;
        return;
      }
      try {
        const res = await fetch(`/api/v1/pets?page=1&spice=${speciesName}`);
        const data = await res.json();
        breedOptions.value = (data.member || []).map((pet) => ({ id: pet.id, name: pet.breed }));
      } catch {
        breedOptions.value = [];
      }
    };

    const fetchSchedules = async () => {
      if (!props.groomer?.id) return;
      try {
        const res = await fetch(`/api/v1/schedules?page=1&master.id=${props.groomer.id}`);
        const data = await res.json();
        schedules.value = data.member || [];
      } catch {
        schedules.value = [];
      }
    };

    const fetchBookings = async (date) => {
      if (!props.groomer?.id || !date) return;
      const formattedDate = date.toISOString().split('T')[0]; // YYYY-MM-DD
      try {
        const res = await fetch(`/api/v1/bookings?page=1&date[after]=${formattedDate}&id_master.id=${props.groomer.id}`);
        const data = await res.json();
        bookings.value = data.member || [];
      } catch {
        bookings.value = [];
      }
    };

    const fetchClientByEmail = async (email) => {
      if (!email) {
        client.value.id = null;
        emailError.value = '';
        return;
      }
      try {
        const res = await fetch(`/api/v1/v1/clients/find-by-email?email=${encodeURIComponent(email)}`);
        const data = await res.json();
        if (res.ok) {
          client.value.id = data.id;
          emailError.value = '';
          showFullForm.value = false;
        } else {
          client.value.id = null;
          if (!showFullForm.value) {
            emailError.value = data.error || 'Client not found';
          } else {
            emailError.value = '';
          }
        }
      } catch {
        client.value.id = null;
        emailError.value = 'Server error';
      }
    };

    function getTimeStop(timeStart) {
      const [h, m] = timeStart.split(':').map(Number)
      const date = new Date()
      date.setHours(h, m)
      date.setMinutes(date.getMinutes() + 30)
      return `${date.getHours().toString().padStart(2,'0')}:${date.getMinutes().toString().padStart(2,'0')}`
    }

    const submitBooking = async () => {
      console.log('submitBooking called');

      const formatTime = (time) => time.length === 5 ? `${time}:00` : time;
      const timeStop = formatTime(getTimeStop(selectedTime.value));

      if (!selectedServices.value.length || !selectedBreed.value || !selectedDate.value || !selectedTime.value) {
        alert('Заповніть всі обов’язкові поля');
        return;
      }

      const baseData = {
        masterId: props.groomer.id,
        services: selectedServices.value.map(s => s.id),
        date: selectedDate.value.toISOString().split('T')[0],
        timeStart: formatTime(selectedTime.value),
        timeStop: timeStop,
        petId: selectedBreed.value.id,
      };

      let url = '/api/v1/bookings';
      let method = 'POST';
      let payload = {};

      if (client.value.id) {
        payload = { ...baseData, clientId: client.value.id };
      }

      else if (client.value.name && client.value.surname && client.value.email && client.value.phone) {
        url = '/api/v1/bookings/with-new-client';
        payload = {
          ...baseData,
          clientName: client.value.name,
          clientSurname: client.value.surname,
          clientEmail: client.value.email,
          clientPhone: client.value.phone,
        };
      }

      else {
        alert('Заповніть усі дані клієнта або введіть існуючий email');
        return;
      }

      try {
        const res = await fetch(url, {
          method,
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify(payload),
        });

        const data = await res.json();

        if (res.ok) {
          alert('Бронювання створено!');
          close();
        } else {
          console.error('Server error:', data);
          alert(data.error || (data.errors ? data.errors.join(', ') : 'Помилка при бронюванні'));
        }
      } catch (e) {
        console.error('Request failed:', e);
        alert('Помилка з’єднання з сервером');
      }
    };

    const open = async () => {
      await nextTick();
      visible.value = true;
      await fetchServices();
      await fetchSchedules();
      await fetchBookings(new Date());
    };

    const close = () => {
      visible.value = false;
      selectedServices.value = [];
      selectedSpecies.value = null;
      selectedBreed.value = null;
      breedOptions.value = [];
      schedules.value = [];
      selectedDate.value = null;
      selectedTime.value = null;
    };

    defineExpose({ open, close });

    watch(selectedSpecies, (newSpecies) => {
      if (newSpecies) fetchBreeds(newSpecies.name);
      else breedOptions.value = [];
    });

    watch(() => props.groomer, (g) => {
      if (!g) serviceOptions.value = [];
    });

    watch(() => client.value.email, (newEmail) => {
      fetchClientByEmail(newEmail);
    });

    watch(showFullForm, (newValue) => {
      if (newValue) {
        emailError.value = '';
      } else {
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

    return { visible, open, close, speciesOptions, selectedSpecies, serviceOptions, selectedServices, breedOptions, selectedBreed, schedules, bookings, client, emailError, showFullForm, selectedDate, selectedTime, submitBooking };
  }
};
</script>

<style>
@import "vue-multiselect/dist/vue-multiselect.min.css";

.multiselect__tag {
  background-color: #ccc;
  color: #000;
}

.multiselect__option--selected {
  background-color: #eee;
  color: #000;
}

.multiselect__option--highlight {
  background-color: #ddd;
}

.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.5);
  overflow-y: auto;
  z-index: 1050;
  display: flex;
  align-items: center;
  justify-content: center;
}

.modal-dialog {
  margin: 0;
}

.modal-content {
  max-width: 600px;
  width: 100%;
  overflow: visible;
}
</style>
