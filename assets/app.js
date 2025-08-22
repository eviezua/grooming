import { createApp } from 'vue';
import translations from '../locales/messages.json';
import './styles/app.scss';
import FaqList from './js/components/FaqList.vue';
import StarRating from './js/components/StarRating.vue';
import GroomerList from "./js/components/GroomerList.vue";
import SearchInput from './js/components/SearchInput.vue'
import FilterPanel from "./js/components/FilterPanel.vue";
import GroomerCatalog from "./js/components/GroomerCatalog.vue";
import JoinForm from "./js/components/JoinForm.vue";
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

app.config.globalProperties.$t = t

app.component('FaqList', FaqList)
app.component('GroomerList', GroomerList)
app.component('StarRating', StarRating)
app.component('SearchInput', SearchInput)
app.component('FilterPanel', FilterPanel)
app.component('GroomerCatalog', GroomerCatalog)
app.component('JoinForm', JoinForm)
app.mount('#app-vue')
