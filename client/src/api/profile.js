import api from "./client";

export const getProfile = (profileHandle) =>
  api.get(`/p/${profileHandle}`).then((r) => {
    return r.data;
  });
