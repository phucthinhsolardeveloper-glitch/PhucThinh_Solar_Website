import { proxyBackendMedia } from '../../utils/media-proxy'

export default defineEventHandler((event) => proxyBackendMedia(event, `/thumbs/${getRouterParam(event, 'path') ?? ''}`))
