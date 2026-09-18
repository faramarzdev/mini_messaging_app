import api from "./client";

export const getConversations = () =>
  api.get("/chats/my").then((r) => {
    const { data, meta } = r.data;
    return { conversations: data, meta };
  });
