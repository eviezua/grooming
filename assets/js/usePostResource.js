import { toast } from "vue3-toastify";
import { ref } from "vue";

export function useSubmit() {
    const loading = ref(false);

    const submit = async ({ url, required = [], payload, onSuccess, successMessage = "Успіх" }) => {
        loading.value = true;

        try {
            for (const field of required) {
                const value = payload[field];
                if (
                    value === undefined ||
                    value === null ||
                    (typeof value === 'string' && value.trim() === '') ||
                    (Array.isArray(value) && value.length === 0)
                ) {
                    toast.error(`Заповніть поле: ${field}`);
                    return null;
                }
            }

            const res = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(payload)
            });

            const data = await res.json();

            if (!res.ok) {
                if (data.errors && typeof data.errors === 'object') {
                    Object.values(data.errors).flat().forEach(msg => toast.error(msg));
                } else {
                    toast.error(data.error || "Помилка сервера");
                }
                return null;
            }

            toast.success(successMessage);
            onSuccess?.(data);
            return data;

        } catch (e) {
            toast.error("Помилка з’єднання з сервером");
            return null;

        } finally {
            loading.value = false;
        }
    };

    return { submit, loading };
}
