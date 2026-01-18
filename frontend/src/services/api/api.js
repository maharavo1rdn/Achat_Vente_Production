import axios from "axios";

const baseURL = import.meta.env.VITE_API_BASE_URL || "/api";

const apiClient = axios.create({
  baseURL,
  headers: {},
});

apiClient.interceptors.request.use((config) => {
  if (config.data instanceof FormData) {
    delete config.headers["Content-Type"];
    console.log("API POST (FormData) →", config.url, {
      files: [...config.data.entries()].map(([k, v]) => `${k}: ${v.name || v}`),
    });
  } else {
    config.headers["Content-Type"] = "application/json";
    console.log(
      "API",
      config.method.toUpperCase(),
      "→",
      config.url,
      config.params || config.data || {}
    );
  }

  return config;
});

apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    console.error("API ERROR →", error.response?.status, error.response?.data || error.message);
    return Promise.reject(error);
  }
);

export default apiClient;
