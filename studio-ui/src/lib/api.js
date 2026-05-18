const BASE = window.location.pathname.startsWith('/studio') ? '/studio' : ''

class Api {
  async auth(password) {
    const r = await fetch(BASE + '/login', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'password=' + encodeURIComponent(password),
      redirect: 'manual',
    })
    return r.status === 302 || r.status === 200
  }

  async call(url, opts = {}) {
    const r = await fetch(BASE + '/api' + url, {
      headers: { 'Content-Type': 'application/json', ...opts.headers },
      ...opts,
    })
    if (r.status === 401) throw new Error('Unauthorized')
    return r.json()
  }

  stats()     { return this.call('/stats') }
  routes()    { return this.call('/routes') }
  routeSave(d) { return this.call('/routes', { method: 'POST', body: JSON.stringify(d) }) }
  routeDelete(m, p) { return this.call('/routes/delete', { method: 'POST', body: JSON.stringify({ method: m, path: p }) }) }
  routeValidate(d) { return this.call('/routes/validate', { method: 'POST', body: JSON.stringify(d) }) }
  dbTables()  { return this.call('/db/tables') }
  dbTable(t)  { return this.call('/db/table/' + t) }
  dbQuery(sql) { return this.call('/db/query', { method: 'POST', body: JSON.stringify({ sql }) }) }
  files()     { return this.call('/files') }
  fileGet(d, n) { return this.call('/file?dir=' + d + '&name=' + n) }
  fileSave(d, n, c) { return this.call('/file', { method: 'POST', body: JSON.stringify({ dir: d, name: n, content: c }) }) }
  logs()      { return this.call('/logs') }
  nis2Stats() { return this.call('/nis2') }
  totp()      { return this.call('/nis2/totp') }
  encrypt(d)  { return this.call('/nis2/encrypt', { method: 'POST', body: JSON.stringify({ data: d }) }) }
  decrypt(d)  { return this.call('/nis2/decrypt', { method: 'POST', body: JSON.stringify({ data: d }) }) }
  policy(p)   { return this.call('/nis2/policy', { method: 'POST', body: JSON.stringify({ password: p }) }) }
  backup(t)   { return this.call('/nis2/backup', { method: 'POST', body: JSON.stringify({ type: t }) }) }
  audit()     { return this.call('/nis2/audit') }
  alert(l, m) { return this.call('/nis2/alert', { method: 'POST', body: JSON.stringify({ level: l, msg: m }) }) }
  proxy(m, u, h, b) { return this.call('/request', { method: 'POST', body: JSON.stringify({ method: m, url: u, headers: h, body: b }) }) }
}

export const api = new Api()
