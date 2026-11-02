const json = async (res) => {
  if (!res.ok) throw new Error((await res.text()) || res.statusText);
  return res.json();
};

const headers = { 'Content-Type': 'application/json', Accept: 'application/json' };

export const api = {
  start: (state, scenario) =>
    fetch('/api/session/start', { method: 'POST', headers, body: JSON.stringify({ state, scenario }) }).then(json),

  select: (id, mode) =>
    fetch(`/api/session/${id}/select`, { method: 'POST', headers, body: JSON.stringify({ mode }) }).then(json),

  playlist: (id, type) =>
    fetch(`/api/session/${id}/playlist${type ? `?type=${type}` : ''}`).then(json),

  quiz: (id) => fetch(`/api/session/${id}/quiz`).then(json),

  feedback: (id, rating, via = 'touch') =>
    fetch(`/api/session/${id}/feedback`, { method: 'POST', headers, body: JSON.stringify({ rating, via }) }).then(json),

  control: (action, value) =>
    fetch('/api/control', { method: 'POST', headers, body: JSON.stringify({ action, value }) }).then(json),
};

export const mmss = (sec) => {
  const s = Math.max(0, Math.round(sec));
  return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`;
};

export const minutes = (sec) => Math.max(0, Math.round(sec / 60));
