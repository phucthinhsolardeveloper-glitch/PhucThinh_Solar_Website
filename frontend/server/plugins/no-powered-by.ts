// Do not advertise the server software on rendered pages.
export default defineNitroPlugin((nitroApp) => {
  nitroApp.hooks.hook('render:response', (response) => {
    delete response.headers['x-powered-by']
  })
})
