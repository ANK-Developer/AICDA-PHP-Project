import { useGetGalleryImagesQuery } from "./galleryApi";

const EMPTY = [];

// Gallery images for one category, with a stable empty array while loading/failed.
// `options` can carry { page, limit, search, resourceType } — the server then returns
// just that page and `count` is the total number of matches.
export function useGalleryImages(category, options) {
  const { data, isLoading, error } = useGetGalleryImagesQuery({ category, ...options });
  return { images: data?.gallery ?? EMPTY, count: data?.count ?? 0, isLoading, error };
}
