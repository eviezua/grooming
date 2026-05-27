<template>
  <form class="form-input-wrapper position-relative justify-content-center mx-auto d-flex flex-column" @submit.prevent="submitForm">
    <h2 class="form-text mx-auto my-5">
      {{ currentMode === 'register' ? $t('Join the team of groomers') : $t('Login to your account') }}
    </h2>

    <template v-if="currentMode === 'register'">
      <form-wizard id="joinForm"
                   :next-button-text="$t('Next')"
                   :back-button-text="$t('Back')"
                   @on-complete="submitForm"
      >
        <template v-slot:header>
          <div class="d-none"></div>
        </template>
        <tab-content :title="$t('Basic info')">
          <input v-model="form.name" class="form-input mx-auto mb-3" type="text" :placeholder="$t('Name')" required />
          <input v-model="form.surname" class="form-input mx-auto mb-3" type="text" :placeholder="$t('Surname')" required />
          <input v-model="form.email" class="form-input mx-auto mb-3" type="email" :placeholder="$t('Email')" required />
          <input v-model="form.honeyPot" type="text" class="hp-field" tabindex="-1" autocomplete="off" />
        </tab-content>
        <tab-content :title="$t('Location and contact')">
          <div class="d-flex flex-column gap-3 mb-3">
            <BaseDropdown
                :options="cities"
                v-model="selectedCity"
                :label="$t('City')"
                resource="cities"
            />
            <BaseDropdown
                :options="districts"
                v-model="selectedDistrict"
                :label="$t('District')"
                resource="districts"
            />
            <input v-model="form.address" class="form-input mx-auto" type="text" :placeholder="$t('Address')" required />
            <input v-model="form.phone" class="form-input mx-auto" type="tel" :placeholder="$t('Phone')" required />
          </div>
        </tab-content>
        <tab-content :title="$t('Security')">
          <input v-model="form.password" class="form-input mx-auto mb-3" type="password" :placeholder="$t('Create password')" required />
          <input v-model="form.repeatPassword" class="form-input mx-auto mb-3" type="password" :placeholder="$t('Repeat password')" required />
        </tab-content>
        <template v-slot:finish>
          <button type="submit" class="form-btn align-items-center d-flex flex-wrap justify-content-between mx-auto my-2">
            <p class="m-auto">{{ isLoading ? $t('Processing...') : $t('Send a form') }}</p>
            <img src="/uploads/icons/button_white_orange_up.png" alt="Submit icon" />
          </button>
        </template>
      </form-wizard>
    </template>
    <template v-else-if="currentMode === 'login'">
      <input v-model="form.email" class="form-input mx-auto" type="email" :placeholder="$t('Email')" required />
      <input v-model="form.password" class="form-input mx-auto" type="password" :placeholder="$t('Password')" required />
      <button type="submit" class="form-btn align-items-center d-flex flex-wrap justify-content-between mx-auto my-2">
        <p class="m-auto">{{ isLoading ? $t('Processing...') : $t('Login') }}</p>
        <img src="/uploads/icons/button_white_orange_up.png" alt="Submit icon" />
      </button>
    </template>
  </form>
</template>

<script setup>
import {reactive, defineProps, ref, onMounted, watch } from 'vue';
import { useSubmit } from "../usePostResource";
import { useApiFetch } from "../useFetchResource";
import {toast} from "vue3-toastify";
import BaseDropdown from './BaseDropdown.vue';

const props = defineProps({
  mode: {
    type: String,
    default: 'register'
  }
});

const currentMode = ref(props.mode);
const { submit, loading: isLoading } = useSubmit();
const { fetchData } = useApiFetch();

const cities = ref([]);
const districts = ref([]);
const selectedCity = ref(null);
const selectedDistrict = ref(null);

const form = reactive({
  name: '',
  surname: '',
  email: '',
  honeyPot: '',
  address: '',
  phone: '',
  password: '',
  repeatPassword: ''
});

const toggleMode = () => {
  currentMode.value = currentMode.value === 'register' ? 'login' : 'register';
}

watch(selectedCity, async (newCity) => {
  if (!newCity) {
    districts.value = []
    selectedDistrict.value = null
    return
  }
  const data = await fetchData('/api/v1/districts', { 'city.id[]': newCity.id }, json =>
      (json['hydra:member'] || json['member'] || []).map(d => ({ id: d.id, name: d.name }))
  )
  if (data) districts.value = data
  selectedDistrict.value = null
});

const submitForm = async () => {
  if (currentMode.value === 'login') {
    await submit({
      url: '/api/v1/login_check',
      payload: {
        email: form.email,
        password: form.password
      },
      required: ['email', 'password'],
      successMessage: $t('Successful login!'),
      onSuccess: (data) => {
        if (data.token) {
          localStorage.setItem('jwt_token', data.token);
          const payload = JSON.parse(atob(data.token.split('.')[1]));

          if (payload.roles && payload.roles.includes('ROLE_ADMIN')) {
            window.location.href = '/admin';
          } else {
            window.location.href = '/';
          }
        }
      }
    });
  } else {
    if (form.password !== form.repeatPassword) {
      toast.error($t('Passwords do not match!'));
      return;
    }

    const payload = {
      honeyPot: form.honeyPot,
      name: form.name,
      surname: form.surname,
      cityId: selectedCity.value?.id,
      districtId: selectedDistrict.value?.id,
      address: form.address,
      email: form.email,
      phone: form.phone,
      password: form.password
    }

    await submit({
      url: '/api/v1/masters',
      payload: payload,
      successMessage: $t('Success! Please check your email to verify your account.')
    });
  }
}

  onMounted(() => {
    const params = new URLSearchParams(window.location.search);

    if (params.has('success')) {
      toast.success($t('Welcome! Your email has been verified. You can now login.'), {
        autoClose: 5000,
      });
    }
    if (params.has('already')) {
      toast.info($t('Your email has already been verified.'), {
        autoClose: 5000,
      });
    }

    fetchData('/api/v1/cities', {}, j =>
        (j['hydra:member'] || j['member'] || []).map(i => ({ id: i.id, name: i.city }))
    ).then(d => cities.value = d || [])
  });
</script>
