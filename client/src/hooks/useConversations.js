import { useQuery } from "@tanstack/react-query";
import { getConversations } from "../api/conversations";

export default function useConversations() {
  const query = useQuery({
    queryKey: ["conversations"],
    queryFn: getConversations,
  });

  return {
    conversations: query.data?.conversations ?? [],
    meta: query.data?.meta,

    isLoading: query.isLoading,
    isFetching: query.isFetching,
    isError: query.isError,
    error: query.error,
    refetch: query.refetch,
  };
}
