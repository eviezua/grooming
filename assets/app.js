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
import MasterProfile from "./js/components/MasterProfile.vue";
import EditPasswordModal from "./js/components/EditPasswordModal.vue";
import PetsSpecializationModal from "./js/components/PetsSpecializationModal.vue";
import PhotoCropModal from "./js/components/PhotoCropModal.vue";
import LocationEdit from "./js/components/LocationEdit.vue";
import MasterBooking from "./js/components/MasterBooking.vue";
import BookingModal from "./js/components/BookingModal.vue";
import MasterClient from "./js/components/MasterClient.vue";
import MasterService from "./js/components/MasterService.vue";
import MasterServiceModal from "./js/components/MasterServiceModal.vue";
import MasterSchedule from "./js/components/MasterSchedule.vue";
import { Cropper } from 'vue-advanced-cropper';
import 'vue-advanced-cropper/dist/style.css';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import OtherFiltersForm from "./js/components/OtherFiltersForm.vue";
import MercureListener from "./js/components/MercureListener.vue";

const currentLang = document.documentElement.lang || 'ua'

function t(key) {
    return translations[currentLang]?.[key] ?? key
}

window.$t = t;

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
app.component('OtherFiltersForm', OtherFiltersForm);
app.component('MercureListener', MercureListener);
app.component('MasterProfile', MasterProfile);
app.component('EditPasswordModal', EditPasswordModal);
app.component('PetsSpecializationModal', PetsSpecializationModal);
app.component('PhotoCropModal', PhotoCropModal);
app.component('LocationEdit', LocationEdit);
app.component('MasterBooking', MasterBooking);
app.component('BookingModal', BookingModal);
app.component('MasterClient', MasterClient);
app.component('MasterService', MasterService);
app.component('MasterServiceModal', MasterServiceModal);
app.component('MasterSchedule', MasterSchedule);
app.component('VueDatePicker', VueDatePicker);
app.component('VueMultiselect', VueMultiselect);
app.component('Cropper', Cropper);
app.mount('#app-vue');

function initAdminMercure() {
    const el = document.getElementById('mercure-root');

    if (el && el.dataset.topic) {
        console.log('✅ Found topic, mounting Vue:', el.dataset.topic);
        const app = createApp(MercureListener, { topic: el.dataset.topic });
        app.mount(el);
    }
}

document.addEventListener('DOMContentLoaded', initAdminMercure);
document.addEventListener('ea.page-loaded', initAdminMercure);