import { withPageMeta } from "@/components/common/withPageMeta";
import { useMemo, useState } from "react";
import { ArrowLeft } from "lucide-react";
import { PageShell } from "@/components/site/PageShell";
import {
  OfficeBearerSlide,
  OfficeBearerSlideSkeleton,
  mapMemberToBearer,
  mapPartnerToBearer,
} from "@/components/site/BearerSlide";
import { Input } from "@/components/ui/input";
import { Button } from "@/components/ui/button";
import { useGetPublicManagementQuery } from "@/features/members/membersApi";
import directoryNotice from "@/assets/directory-notice.webp";
import idCardSample from "@/assets/id-card-sample.jpg";

const PAGE_META = [
  { title: "Directory · AICDA" },
  {
    name: "description",
    content: "Find an AICDA member or partner by Member ID or Partner ID.",
  },
  { property: "og:title", content: "Directory · AICDA" },
  {
    property: "og:description",
    content: "Find an AICDA member or partner by Member ID or Partner ID.",
  },
];

// A card downloaded from this site (member 068), shown under the sidebar.
function IdCardSamples() {
  return (
    <div className="mt-6 rounded-lg border border-border bg-card p-3 shadow-(--shadow-card)">
      <h3
        className="mb-3 text-lg font-bold text-primary"
        style={{ fontFamily: "'Playfair Display', serif" }}
      >
        Identity Card
      </h3>
      <img
        src={idCardSample}
        alt="AICDA identity card, front and back"
        className="w-full rounded-md border border-border"
        loading="lazy"
      />
    </div>
  );
}

function Page() {
  const [searchInput, setSearchInput] = useState("");
  const [search, setSearch] = useState("");

  // Nothing is requested until an ID is searched; the notice image is shown instead.
  const { data, isFetching, isError } = useGetPublicManagementQuery(
    { page: 1, limit: 10, search },
    { skip: !search },
  );
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
  };

  const goBack = () => {
    setSearch("");
    setSearchInput("");
  };

  return (
    <PageShell
      title="Directory"
      subtitle="Find a member or partner by Member ID or Partner ID."
      sidebarExtra={<IdCardSamples />}
    >
      <div className="flex flex-wrap items-center justify-between gap-4">
        <h2 className="text-2xl font-black text-foreground">Search Member</h2>

        <form onSubmit={submitSearch} className="flex">
          <Input
            type="search"
            value={searchInput}
            onChange={(e) => {
              setSearchInput(e.target.value);
              if (!e.target.value.trim() && search) setSearch("");
            }}
            placeholder="Member ID or Partner ID"
            aria-label="Search by Member ID or Partner ID"
            className="w-48 rounded-r-none focus-visible:ring-0 sm:w-64"
          />
          <Button type="submit" variant="secondary" className="rounded-l-none border border-l-0">
            Search
          </Button>
        </form>
      </div>

      {!search ? (
        <img
          src={directoryNotice}
          alt="Notice from AICDA: please take care before buying or selling a used car"
          className="mx-auto mt-6 block h-auto w-full max-w-3xl rounded-lg border border-border"
        />
      ) : (
        <div className="mt-6 space-y-6">
          <Button type="button" variant="outline" size="sm" onClick={goBack}>
            <ArrowLeft /> Back
          </Button>

          {isFetching ? (
            <OfficeBearerSlideSkeleton />
          ) : isError ? (
            <p className="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
              Could not load the details. Please try again.
            </p>
          ) : bearers.length === 0 ? (
            <p className="rounded-xl border border-dashed border-border p-8 text-center text-sm text-muted-foreground">
              No member or partner found with this ID.
            </p>
          ) : (
            bearers.map((bearer) => <OfficeBearerSlide key={bearer.id} bearer={bearer} />)
          )}
        </div>
      )}
    </PageShell>
  );
}

export default withPageMeta(Page, PAGE_META);
