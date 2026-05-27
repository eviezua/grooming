import { ref, onUnmounted } from 'vue'

export function useMercure({ topic, onMessage, hubUrl = 'https://localhost/.well-known/mercure' }) {
    const es = ref(null)

    const connect = () => {
        try {
            const url = new URL(hubUrl)
            url.searchParams.append('topic', topic)

            es.value?.close()

            es.value = new EventSource(url.toString(), { withCredentials: false })

            es.value.onopen = () => {
                console.log(`🚀 [Mercure] З'єднання встановлено для топіка: ${topic}`)
            }

            es.value.onmessage = async (event) => {
                console.log('📩 [Mercure] Отримано нове повідомлення.')
                if (onMessage) {
                    try {
                        const parsedData = JSON.parse(event.data)
                        await onMessage(parsedData)
                    } catch (e) {
                        await onMessage(event.data)
                    }
                }
            }

            es.value.onerror = (err) => {
                console.error('❌ [Mercure] Помилка. ReadyState:', es.value.readyState);
                es.value.close();
                setTimeout(connect, 5000)
            }
        } catch (err) {
            console.error('💥 [Mercure] Не вдалося ініціалізувати EventSource', err)
        }
    }

    connect()

    onUnmounted(() => {
        if (es.value) {
            es.value.close()
            console.log(`ℹ️ [Mercure] З'єднання закрито для: ${topic}`)
        }
    })

    return { es }
}