import { useRef, useState } from "react";
import { Download, Loader2, Share2, ThumbsDown, ThumbsUp, User } from "lucide-react";
import { toCanvas } from "html-to-image";
import { toast } from "react-toastify";
import { Skeleton } from "@/components/ui/skeleton";
import { MemberIdCard, MemberIdCardBack } from "@/components/site/MemberIdCard";
import { getMediaUrl } from "@/lib/config";

// The member / partner detail block shared by the Management and Directory pages.
export function mapMemberToBearer(member) {
  return {
    id: member.id,
    name: member.memberName || "",
    designation: member.designation || "",
    companyName: member.companyName || "",
    sOf: member.fatherName || "",
    officeAddress: member.companyAddress || "",
    city: member.city || "",
    state: member.state || "",
    companyTel: member.companyTelephone || "",
    pNo: member.packetNo || "",
    residence: member.residentialAddress || "",
    residentTel: member.residentialTelephone || "",
    dateOfJoining: formatDate(member.dateOfJoining) || "",
    mobile: member.mobile || "",
    memberId: member.memberId || "",
    validityFrom: member.validityFrom || "",
    validityTo: member.validityTo || "",
    active: Boolean(member.isActive),
    photo: member.photo ? getMediaUrl(member.photo) : null,
  };
}

export function mapPartnerToBearer(partner) {
  return {
    id: `partner-${partner.id}`,
    name: partner.partnerName || "",
    designation: partner.designation || "Partner",
    companyName: partner.companyName || "",
    sOf: partner.fatherName || "",
    officeAddress: partner.companyAddress || "",
    city: partner.city || "",
    state: partner.state || "",
    companyTel: partner.companyTelephone || "",
    pNo: partner.packetNo || "",
    residence: partner.residentialAddress || "",
    residentTel: partner.residentialTelephone || "",
    mobile: partner.mobile || "",
    memberId: partner.partnerId || "",
    dateOfJoining: formatDate(partner.dateOfJoining),
    validityFrom: formatDate(partner.validityFrom),
    validityTo: formatDate(partner.validityTo),
    active: Boolean(partner.isActive),
    photo: partner.photo ? getMediaUrl(partner.photo) : null,
  };
}

async function combineCardCanvases(frontNode, backNode) {
  const [frontCanvas, backCanvas] = await Promise.all([
    toCanvas(frontNode, {
      pixelRatio: 2,
      backgroundColor: "#ffffff",
      cacheBust: true,
      filter: (el) => el.dataset?.exportIgnore !== "true",
    }),
    toCanvas(backNode, { pixelRatio: 2, backgroundColor: "#ffffff", cacheBust: true }),
  ]);

  const gap = 48;
  const width = Math.max(frontCanvas.width, backCanvas.width);
  const height = frontCanvas.height + gap + backCanvas.height;

  const combined = document.createElement("canvas");
  combined.width = width;
  combined.height = height;
  const ctx = combined.getContext("2d");
  ctx.fillStyle = "#ffffff";
  ctx.fillRect(0, 0, width, height);
  ctx.drawImage(frontCanvas, (width - frontCanvas.width) / 2, 0);
  ctx.drawImage(backCanvas, (width - backCanvas.width) / 2, frontCanvas.height + gap);

  return combined;
}

function canvasToBlob(canvas, quality = 0.95) {
  return new Promise((resolve) => canvas.toBlob(resolve, "image/jpeg", quality));
}

async function downloadCombinedCard(frontNode, backNode, fileName) {
  const canvas = await combineCardCanvases(frontNode, backNode);
  const dataUrl = canvas.toDataURL("image/jpeg", 0.95);
  const a = document.createElement("a");
  a.href = dataUrl;
  a.download = fileName;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

function cardSummary(bearer) {
  return [
    bearer.name.trim(),
    bearer.designation,
    bearer.officeAddress,
    [bearer.city, bearer.state].filter(Boolean).join(", "),
    bearer.mobile && `Mobile: ${bearer.mobile}`,
  ]
    .filter(Boolean)
    .join("\n");
}

async function shareCard(bearer, frontNode, backNode) {
  const title = bearer.name.trim() || "AICDA Member";
  const text = cardSummary(bearer);
  const baseName = title.replace(/\s+/g, "_");

  let file = null;
  try {
    if (frontNode && backNode) {
      const canvas = await combineCardCanvases(frontNode, backNode);
      const blob = await canvasToBlob(canvas);
      if (blob) file = new File([blob], `${baseName}_id_card.jpg`, { type: "image/jpeg" });
    }
  } catch {
    // fall through to text sharing below
  }

  if (file && navigator.canShare?.({ files: [file] })) {
    try {
      await navigator.share({ title, text, files: [file] });
      return;
    } catch (err) {
      if (err && err.name === "AbortError") return; // user cancelled the share sheet
      // otherwise fall through to the text-only share below
    }
  }

  if (navigator.share) {
    try {
      await navigator.share({ title, text });
      return;
    } catch (err) {
      if (err && err.name === "AbortError") return;
    }
  }

  try {
    await navigator.clipboard.writeText(text);
    toast.success("Member details copied to clipboard");
  } catch {
    toast.error("Couldn't share the card. Please try again.");
  }
}

function DetailRow({ label, value }) {
  return (
    <p className="text-sm">
      <span className="font-bold text-foreground">{label} :</span>{" "}
      <span className="text-foreground/80">{value}</span>
    </p>
  );
}

export function OfficeBearerSlide({ bearer }) {
  const idCardRef = useRef(null);
  const backCardRef = useRef(null);
  const [exporting, setExporting] = useState(false);
  const [sharing, setSharing] = useState(false);

  const handleDownload = async () => {
    if (!idCardRef.current || !backCardRef.current || exporting) return;
    setExporting(true);
    try {
      const baseName = bearer.name.trim().replace(/\s+/g, "_") || "member";
      await downloadCombinedCard(idCardRef.current, backCardRef.current, `${baseName}_id_card.jpg`);
    } catch {
      toast.error("Couldn't generate the image. Please try again.");
    } finally {
      setExporting(false);
    }
  };

  const handleShare = async () => {
    if (sharing) return;
    setSharing(true);
    try {
      await shareCard(bearer, idCardRef.current, backCardRef.current);
    } finally {
      setSharing(false);
    }
  };

  return (
    <div className="grid grid-cols-1 items-stretch gap-3 sm:grid-cols-[220px_1fr]">
      <div className="flex w-full items-start justify-center">
        {bearer.photo ? (
          <img
            src={bearer.photo}
            alt={bearer.name}
            className="h-52 w-40 rounded-lg bg-muted/40 object-contain"
            crossOrigin="anonymous"
          />
        ) : (
          <div className="flex h-52 w-40 items-center justify-center rounded-lg bg-muted/40">
            <User className="h-16 w-16 text-muted-foreground/40" />
          </div>
        )}
      </div>
      <div className="relative rounded-xl border border-border bg-card p-6 shadow-(--shadow-card)">
        <div className="relative">
          <h3 className="text-center text-lg font-bold text-primary">Member Detail</h3>
          <div className="absolute right-0 top-0 flex gap-1.5">
            <button
              type="button"
              onClick={handleDownload}
              disabled={exporting}
              title="Download ID card"
              aria-label="Download ID card"
              className="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:opacity-50"
            >
              {exporting ? (
                <Loader2 className="h-5 w-5 animate-spin" />
              ) : (
                <Download className="h-5 w-5" />
              )}
            </button>
            <button
              type="button"
              onClick={handleShare}
              disabled={sharing}
              title="Share card"
              aria-label="Share card"
              className="rounded-md p-2 text-muted-foreground transition-colors hover:bg-accent hover:text-foreground disabled:opacity-50"
            >
              {sharing ? (
                <Loader2 className="h-5 w-5 animate-spin" />
              ) : (
                <Share2 className="h-5 w-5" />
              )}
            </button>
          </div>
        </div>
        <span
          title={bearer.active ? "Active" : "Inactive"}
          aria-label={bearer.active ? "Active" : "Inactive"}
          className={`absolute bottom-3 right-3 flex items-center justify-center ${
            bearer.active ? "text-emerald-500" : "text-red-500"
          }`}
        >
          {bearer.active ? (
            <ThumbsUp className="h-8 w-8 fill-emerald-500" />
          ) : (
            <ThumbsDown className="h-8 w-8 fill-red-500" />
          )}
        </span>
        <div className="mt-3 space-y-2">
          <DetailRow label="Name" value={bearer.name} />
          <DetailRow label="ID" value={bearer.memberId} />
          <DetailRow label="Designation" value={bearer.designation} />
          <DetailRow label="S/o" value={bearer.sOf} />
          <DetailRow label="Office Address" value={bearer.officeAddress} />
          <DetailRow label="City" value={bearer.city} />
          <DetailRow label="State" value={bearer.state} />
          <DetailRow label="Company Tel" value={bearer.companyTel} />
          <DetailRow label="P.No." value={bearer.pNo} />
          <DetailRow label="Residence" value={bearer.residence} />
          <DetailRow label="Resident Tel" value={bearer.residentTel} />
          <DetailRow label="Date of Joining" value={bearer.dateOfJoining} />
          <DetailRow label="Mobile" value={bearer.mobile} />
        </div>
      </div>

      {/* Rendered off-screen purely so it can be captured as the downloaded JPG */}
      <div aria-hidden="true" style={{ position: "fixed", top: 0, left: "-9999px", zIndex: -1 }}>
        <MemberIdCard bearer={bearer} cardRef={idCardRef} />
        <MemberIdCardBack bearer={bearer} cardRef={backCardRef} />
      </div>
    </div>
  );
}

export function OfficeBearerSlideSkeleton() {
  return (
    <div className="grid grid-cols-1 items-stretch gap-3 sm:grid-cols-[220px_1fr]">
      <Skeleton className="mx-auto h-52 w-40 rounded-lg" />
      <div className="rounded-xl border border-border bg-card p-6 shadow-(--shadow-card)">
        <Skeleton className="mx-auto h-5 w-32" />
        <div className="mt-4 space-y-3">
          {Array.from({ length: 8 }).map((_, index) => (
            <Skeleton key={index} className="h-3.5 w-full" />
          ))}
        </div>
      </div>
    </div>
  );
}

function formatDate(value) {
  if (!value) return "";

  const date = new Date(value);

  if (Number.isNaN(date.getTime())) return "";

  return date.toLocaleDateString("en-GB", {
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
    timeZone: "Asia/Kolkata",
  });
}
