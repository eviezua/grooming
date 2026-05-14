import { toast } from "vue3-toastify";
import { ref } from "vue";

export function useSubmit() {
    const loading = ref(false);

    const submit = async ({ url, required = [], payload, onSuccess, successMessage = "Успіх" }) => {
        loading.value = true;

        try {
            for (const field of required) {
                if (!payload[field] || (typeof payload[field] === 'string' && !payload[field].trim())) {
                    toast.error(`Заповніть поле: ${field}`);
                    loading.value = false;
                    return null;
                }
            }

            const res = await fetch(url, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/ld+json"
                },
                body: JSON.stringify(payload),
                credentials: 'include'
            });

            const contentType = res.headers.get("content-type");
            let data = {};

            if (contentType && contentType.includes("json")) {
                data = await res.json();
            }

            if (!res.ok) {
                if (data.violations && Array.isArray(data.violations)) {
                    data.violations.forEach(v => toast.error(v.message));
                }
                else if (data.detail) {
                    toast.error(data.detail);
                }
                else {
                    toast.error("Помилка сервера");
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
