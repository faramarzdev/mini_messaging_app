import api from "./client";

export const getMessages = (profileHandle) =>
  api.get(`/p/${profileHandle}/messages`).then((r) => {
    const { data, meta } = r.data;
    return { messages: data, meta };
  });

export const sendMessage = (message, receiverHandle, replyId = null) =>
  api
    .post(`/message`, {
      body: message,
      receiver_handle: receiverHandle,
      reply_id: replyId,
    })
    .then((r) => {
      return r.data;
    });
