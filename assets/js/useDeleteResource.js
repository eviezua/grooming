import { toast } from "vue3-toastify";
import { ref } from "vue";

export function useDelete() {
    const loading = ref(false);

    const remove = async (url, successMessage = "Видалено успішно") => {
        if (!confirm("Ви впевнені, що хочете видалити?")) return false;

        loading.value = true;
        try {
            const res = await fetch(url, {
                method: "DELETE",
                credentials: 'include'
            });

            if (res.ok) {
                toast.success(successMessage);
                return true;
            }
            toast.error("Не вдалося видалити");
            return false;
        } catch (e) {
            toast.error("Помилка з’єднання");
            return false;
        } finally {
            loading.value = false;
        }
    };

    return { remove, loading };
}