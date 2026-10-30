import { withPageMeta } from "@/components/common/withPageMeta";
import { useMemo, useRef, useState } from "react";
import { PageShell } from "@/components/site/PageShell";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import {
  OfficeBearerSlide,
  OfficeBearerSlideSkeleton,
  mapMemberToBearer,
  mapPartnerToBearer,
} from "@/components/site/BearerSlide";
import { useGetPublicManagementQuery } from "@/features/members/membersApi";

const PAGE_META = [
  { title: "Management Committee · AICDA" },
  {
    name: "description",
    content: "The elected office bearers and national executive of AICDA.",
  },
  { property: "og:title", content: "Management Committee · AICDA" },
  {
    property: "og:description",
    content: "The elected office bearers and national executive of AICDA.",
  },
];

function OfficeBearersListSkeleton() {
  return (
    <div className="mt-4 space-y-6">
      {Array.from({ length: 3 }).map((_, index) => (
        <OfficeBearerSlideSkeleton key={index} />
      ))}
    </div>
  );
}

function OfficeBearersList({ bearers, searching }) {
  if (bearers.length === 0) {
    return (
      <p className="mt-4 rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
        {searching ? "No member or partner found with this ID." : "No members to show."}
      </p>
    );
  }

  return (
    <div className="mt-4 space-y-6">
      {bearers.map((bearer, index) => (
        <OfficeBearerSlide key={bearer.id || `${bearer.name}-${index}`} bearer={bearer} />
      ))}
    </div>
  );
}

const PAGE_SIZE = 10;

function PaginationBar({ pagination, onPageChange }) {
  const { page, limit, total, totalPages } = pagination;
  if (!total) return null;

  return (
    <nav
      aria-label="Pagination"
      className="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground"
    >
      <p>
        Showing {(page - 1) * limit + 1}–{Math.min(page * limit, total)} of {total}
      </p>
      <div className="flex items-center gap-3">
        <Button
          variant="outline"
          size="sm"
          disabled={page <= 1}
          onClick={() => onPageChange(page - 1)}
        >
          Previous
        </Button>
        <span>
          Page {page} of {totalPages}
        </span>
        <Button
          variant="outline"
          size="sm"
          disabled={page >= totalPages}
          onClick={() => onPageChange(page + 1)}
        >
          Next
        </Button>
      </div>
    </nav>
  );
}

function Page() {
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");
  const [page, setPage] = useState(1);
  const listRef = useRef(null);

  // The server filters (by Member ID / Partner ID only) and paginates.
  const { data, isFetching, isError } = useGetPublicManagementQuery({
    page,
    limit: PAGE_SIZE,
    search,
  });
  const bearers = useMemo(
    () =>
      (data?.items ?? []).map((item) =>
        item.type === "partner" ? mapPartnerToBearer(item) : mapMemberToBearer(item),
      ),
    [data],
  );

  const submitSearch = (event) => {
    event.preventDefault();
    setSearch(searchInput.trim());
    setPage(1);
  };

  const changeSearchInput = (value) => {
    setSearchInput(value);
    if (!value.trim() && search) {
      setSearch("");
      setPage(1);
    }
  };

  const goToPage = (nextPage) => {
    setPage(nextPage);
    listRef.current?.scrollIntoView({ behavior: "smooth", block: "start" });
  };

  return (
    <PageShell
      title="Management Committee"
      subtitle="The elected office bearers and national executive of AICDA."
      bannerKey="management"
    >
      <div className="mt-4 flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-2xl font-black text-foreground">All Members</h2>

        <form onSubmit={submitSearch} className="flex">
          <Input
            type="search"
            value={searchInput}
            onChange={(e) => changeSearchInput(e.target.value)}
            placeholder="Search Member ID or Partner ID"
            aria-label="Search by Member ID or Partner ID"
            className="w-48 rounded-r-none focus-visible:ring-0 sm:w-64"
          />
          <Button type="submit" variant="secondary" className="rounded-l-none border border-l-0">
            Search
          </Button>
        </form>
      </div>

      <div ref={listRef} className="scroll-mt-28">
        {isFetching ? (
          <OfficeBearersListSkeleton />
        ) : isError ? (
          <p className="mt-4 rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
            Could not load the list. Please try again.
          </p>
        ) : (
          <OfficeBearersList bearers={bearers} searching={Boolean(search)} />
        )}
      </div>

      {data?.pagination && !isFetching && (
        <PaginationBar pagination={data.pagination} onPageChange={goToPage} />
      )}
    </PageShell>
  );
}
export default withPageMeta(Page, PAGE_META);
