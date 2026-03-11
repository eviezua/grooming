import { ref } from "vue";
import { toast } from "vue3-toastify";

export function useApiFetch() {
    const loading = ref(false);
    const error = ref(null);

    const fetchData = async (url, params = {}, transform = data => data, successMessage = null) => {
        loading.value = true;
        error.value = null;


        try {
            const queryString = new URLSearchParams(params).toString();
            const res = await fetch(`${url}?${queryString}`);
            const data = await res.json();

            if (!res.ok) {
                throw new Error(data.error || 'Помилка сервера');
            }

            if (successMessage) {
                toast.success(successMessage);
            }

            return transform(data);
        } catch (e) {
            console.error(e);
            error.value = e.message;
            toast.error(e.message);
            return null;
        } finally {
            loading.value = false;
        }
    };

    return { fetchData, loading, error };
}
