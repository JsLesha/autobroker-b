import http from "k6/http";
import { check, sleep } from "k6";

export const options = {
  vus: 20,
  duration: "30s",
  thresholds: {
    http_req_failed: ["rate<0.05"],
    http_req_duration: ["p(95)<800"],
  },
};

const BASE = __ENV.API_URL || "http://localhost:8080/api/v1";

export default function () {
  const login = http.post(`${BASE}/auth/login`, JSON.stringify({
    email: "admin@autobroker.local",
    password: "Password123!",
  }), { headers: { "Content-Type": "application/json" } });

  check(login, { "login 200": (r) => r.status === 200 });
  const token = login.json("token");
  const headers = { Authorization: `Bearer ${token}` };

  const lots = http.get(`${BASE}/lots`, { headers });
  check(lots, { "lots 200": (r) => r.status === 200 });

  const quote = http.post(`${BASE}/calculator/quote`, JSON.stringify({ kind: "sea" }), {
    headers: { "Content-Type": "application/json" },
  });
  check(quote, { "quote 200": (r) => r.status === 200 });
  sleep(1);
}
