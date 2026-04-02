import { toast } from "vue3-toastify";
import { ref } from "vue";

export function useUpdate() {
    const loading = ref(false);

    const updateMaster = async ({ id, payload, method = "PATCH", successMessage = "Дані оновлено" }) => {
        loading.value = true;

        const contentType = method === "PATCH"
            ? "application/merge-patch+json"
            : "application/json";

        try {
            const res = await fetch(`/api/v1/masters/${id}`, {
                method: method,
                headers: { "Content-Type": contentType },
                body: JSON.stringify(payload),
                credentials: 'include'
            });

            if (!res.ok) {
                const data = await res.json();
                toast.error(data.error || data['hydra:description'] || "Помилка оновлення");
                return null;
            }

            const data = await res.json();
            toast.success(successMessage);
            return data;
        } catch (e) {
            toast.error("Помилка з’єднання з сервером");
            return null;
        } finally {
            loading.value = false;
        }
    };

    return { updateMaster, loading };
}