// Full-screen photos open at once on the image the page has already loaded, then sharpen when the large
// rendition has decoded. Nothing is ever shown empty while the server prepares it.
export function useProgressiveImage(source: () => string | null | undefined, preview: (src: string) => string) {
  const src = ref('')
  let token = 0
  watch(source, (value) => {
    const current = ++token
    if (!value) {
      src.value = ''
      return
    }
    src.value = preview(value)
    if (!import.meta.client) return
    const full = viewerImage(value)
    const probe = new Image()
    probe.src = full
    probe.decode().then(() => { if (current === token) src.value = full }).catch(() => {})
  }, { immediate: true })
  return src
}
