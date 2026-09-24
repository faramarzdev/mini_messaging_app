import { useQuery } from "@tanstack/react-query";
import { getMessages } from "../api/messages";

export default function useMessages(profileHandle) {
  const query = useQuery({
    queryKey: ["messages", profileHandle],
    queryFn: () => getMessages(profileHandle),
  });

  return {
    messages: query.data?.messages ?? [],
    meta: query.data?.meta,

    isLoading: query.isLoading,
    isFetching: query.isFetching,
    isError: query.isError,
    error: query.error,
    refetch: query.refetch,
  };
}
