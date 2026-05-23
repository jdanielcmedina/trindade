const API = '/monitor/api'

export async function fetchJson(url, opts) {
  try {
    const r = await fetch(API + url, { credentials: 'same-origin', headers: { 'Content-Type': 'application/json' }, ...opts })
    if (!r.ok) return null
    return r.json()
  } catch { return null }
}

export async function login(user, pass) {
  const r = await fetch(API + '/login', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'user=' + encodeURIComponent(user) + '&password=' + encodeURIComponent(pass),
  })
  if (!r.ok) return { ok: false, error: 'Invalid credentials' }
  return r.json()
}

export async function getStats() { return fetchJson('/stats') }
export async function getLogs() { return fetchJson('/logs') }
export async function getRequests() { return fetchJson('/requests') }
