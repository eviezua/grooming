<template>
  <form class="form-input-wrapper position-relative justify-content-center mx-auto d-flex flex-column" @submit.prevent="submitForm">
    <h2 class="form-text mx-auto my-5">
      {{ currentMode === 'register' ? $t('Join the team of groomers') : $t('Login to your account') }}
    </h2>

    <template v-if="currentMode === 'register'">
      <input v-model="form.name" class="form-input mx-auto" type="text" :placeholder="$t('Name')" required />
      <input v-model="form.surname" class="form-input mx-auto" type="text" :placeholder="$t('Surname')" required />
    </template>
    <input v-model="form.email" class="form-input mx-auto" type="email" :placeholder="$t('Email')" required />
    <input v-model="form.password" class="form-input mx-auto" type="password" :placeholder="$t('Password')" required />
    <button type="submit" class="form-btn align-items-center d-flex flex-wrap justify-content-between mx-auto my-2">
      <p class="m-auto">{{ isLoading ? $t('Processing...') : (currentMode === 'register' ? $t('Send a form') : $t('Login')) }}</p>
      <img src="/uploads/icons/button_white_orange_up.png" alt="Submit icon" />
    </button>
  </form>
</template>

<script setup>
import { reactive, defineProps, ref } from 'vue'
import { useSubmit } from "../usePostResource"

const props = defineProps({
  mode: {
    type: String,
    default: 'register'
  }
})

const currentMode = ref(props.mode)
const { submit, loading: isLoading } = useSubmit()

const form = reactive({
  name: '',
  surname: '',
  email: '',
  password: ''
})
const toggleMode = () => {
  currentMode.value = currentMode.value === 'register' ? 'login' : 'register'
}

const submitForm = async () => {
  if (currentMode.value === 'login') {
    await submit({
      url: '/api/v1/login_check',
      payload: {
        email: form.email,
        password: form.password
      },
      required: ['email', 'password'],
      successMessage: "Ви успішно увійшли!",
      onSuccess: (data) => {
        if (data.token) {
          localStorage.setItem('jwt_token', data.token);
          window.location.href = '/';
        }
      }
    })
  } else {
    console.log('Registration logic:', form)
  }
}
</script>
