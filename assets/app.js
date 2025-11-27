import { createApp } from 'vue';

import translations from '../locales/messages.json';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import 'vue3-form-wizard/dist/style.css';
import 'vue3-toastify/dist/index.css';
import '@vuepic/vue-datepicker/dist/main.css';
import "vue-multiselect/dist/vue-multiselect.min.css";
import './styles/app.scss';

import VueFormWizard from 'vue3-form-wizard';
import Vue3Toastify from 'vue3-toastify';
import VueDatePicker from '@vuepic/vue-datepicker';
import VueMultiselect from 'vue-multiselect';
import FaqList from './js/components/FaqList.vue';
import StarRating from './js/components/StarRating.vue';
import GroomerList from "./js/components/GroomerList.vue";
import SearchInput from './js/components/SearchInput.vue'
import FilterPanel from "./js/components/FilterPanel.vue";
import GroomerCatalog from "./js/components/GroomerCatalog.vue";
import JoinForm from "./js/components/JoinForm.vue";
import BookingCalendarVue from "./js/components/BookingCalendar.vue";
import BookingFormVue from "./js/components/BookingForm.vue";
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

const currentLang = document.documentElement.lang || 'ua'

function t(key) {
    return translations[currentLang]?.[key] ?? key
}

const app = createApp({
    data() {
        return {
            ratings: Array(6).fill(0),
            currentLang
        }
    },
    methods: {
        t
    }
})

const toastOptions = {
    autoClose: 3000,
    position: 'bottom-left',
    limit: 5,
    rtl: false,
};

app.config.globalProperties.$t = t
app.use(VueFormWizard);
app.use(Vue3Toastify, toastOptions);

app.component('FaqList', FaqList);
app.component('GroomerList', GroomerList);
app.component('StarRating', StarRating);
app.component('SearchInput', SearchInput);
app.component('FilterPanel', FilterPanel);
app.component('GroomerCatalog', GroomerCatalog);
app.component('JoinForm', JoinForm);
app.component('BookingCalendarVue', BookingCalendarVue);
app.component('BookingFormVue', BookingFormVue);
app.component('VueDatePicker', VueDatePicker);
app.component('VueMultiselect', VueMultiselect)
app.mount('#app-vue');
