import { withPageMeta } from "@/components/common/withPageMeta";
import { Download, FileText } from "lucide-react";
import { PageShell } from "@/components/site/PageShell";

const PAGE_META = [
  { title: "RTO Form · AICDA" },
  { name: "description", content: "Download all the useful RTO forms." },
  { property: "og:title", content: "RTO Form · AICDA" },
  { property: "og:description", content: "Download all the useful RTO forms." },
];

// The PDFs live in /public/rto-forms.
const FORMS = [
  { title: "Cash Receipt", file: "cash-receipt.pdf" },
  { title: "Form - 26", file: "form-26.pdf" },
  { title: "Form - 28", file: "form-28.pdf" },
  { title: "Form - 29", file: "form-29.pdf" },
  { title: "Form - 30", file: "form-30.pdf" },
  { title: "Insurance Transfer", file: "insurance-transfer.pdf" },
];

function Page() {
  return (
    <PageShell title="RTO Form" subtitle="Download all the useful RTO forms.">
      <p className="font-semibold text-foreground">Dear Member,</p>
      <p className="mt-1 text-foreground/80">
        You can download all the useful RTO forms from the below links.
      </p>

      <ul className="mt-6 divide-y divide-border overflow-hidden rounded-lg border border-border bg-card shadow-(--shadow-card)">
        {FORMS.map((form) => {
          const href = `/rto-forms/${form.file}`;

          return (
            <li key={form.file} className="flex items-center justify-between gap-3 px-4 py-3">
              <a
                href={href}
                target="_blank"
                rel="noopener noreferrer"
                className="flex min-w-0 items-center gap-2 font-semibold text-foreground transition hover:text-primary"
              >
                <FileText className="h-5 w-5 shrink-0 text-primary" />
                <span className="truncate">{form.title}</span>
              </a>
              <a
                href={href}
                download
                aria-label={`Download ${form.title}`}
                title={`Download ${form.title}`}
                className="shrink-0 rounded-md p-2 text-primary transition hover:bg-accent"
              >
                <Download className="h-5 w-5" />
              </a>
            </li>
          );
        })}
      </ul>
    </PageShell>
  );
}

export default withPageMeta(Page, PAGE_META);
