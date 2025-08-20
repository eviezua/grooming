<template>
  <form class="form-input-wrapper position-relative justify-content-center mx-auto d-flex flex-column" @submit.prevent="submitForm">
    <h2 class="form-text mx-auto my-5">{{ $t('Join the team of groomers') }}</h2>
    <div>

    </div>
    <input v-model="form.name" class="form-input mx-auto" type="text" :placeholder="$t('Name')" required />
    <input v-model="form.surname" class="form-input mx-auto" type="text" :placeholder="$t('Surname')" required />
    <input v-model="form.email" class="form-input mx-auto" type="email" :placeholder="$t('Email')" required />
    <button type="submit" class="form-btn align-items-center d-flex flex-wrap justify-content-between mx-auto my-2">
      <p class="m-auto">{{ $t('Send a form') }}</p>
      <img src="/uploads/icons/button_white_orange_up.png" alt="Submit icon" />
    </button>
  </form>
</template>

<script setup>
import { reactive } from 'vue'

const form = reactive({
  name: '',
  surname: '',
  email: ''
})

const submitForm = () => {
  console.log('Submitting form:', form)
}
</script>
