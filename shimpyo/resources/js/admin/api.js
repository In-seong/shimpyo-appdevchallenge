const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };

const json = async (res) => {
  if (!res.ok) throw new Error((await res.text()) || res.statusText);
  return res.status === 204 ? null : res.json();
};

const call = (method) => (url, body) =>
  fetch(`/api/admin${url}`, { method, headers, body: body ? JSON.stringify(body) : undefined }).then(json);

export const get = call('GET');
export const post = call('POST');
export const put = call('PUT');
export const del = call('DELETE');

export const MODES = ['content', 'rest', 'check', 'play'];
export const MODE_LABEL = { content: '콘텐츠', rest: '휴식', check: '점검', play: '놀이' };

export const pct = (v) => (v === null || v === undefined ? '—' : `${Math.round(v * 100)}%`);
export const mmss = (s) => `${Math.floor(s / 60)}분 ${String(s % 60).padStart(2, '0')}초`;
