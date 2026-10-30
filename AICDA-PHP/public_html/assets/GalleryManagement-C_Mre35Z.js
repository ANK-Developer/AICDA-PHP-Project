import{o as e,t}from"./jsx-runtime-DVpe7PXp.js";import{t as n}from"./react-Dps3yNUO.js";import{E as r}from"./directory-shared-CtjPvLUJ.js";import{t as i}from"./circle-check-CIPqaBlg.js";import{t as a}from"./eye-DGZnmR0v.js";import{n as o,t as s}from"./upload-DPva09hs.js";import{t as c}from"./link-2-Cya99Idt.js";import{t as ee}from"./loader-circle-DT9DtKTa.js";import{t as l}from"./pencil-BzBkmHcW.js";import{a as u,i as d,n as f,t as p}from"./MediaVideo-D98_jrZO.js";import{t as m}from"./plus-DEhSC2aD.js";import{t as h}from"./search-dJz1lfKw.js";import{_ as g,c as _,l as v,u as y,v as b}from"./index-CXECB6AG.js";import{a as x,o as S,r as C,s as w}from"./galleryApi-ooVnZGEx.js";import{t as T}from"./config-CrWrgA7M.js";import{t as E}from"./skeleton-DU1PQKtu.js";import{n as D,t as O}from"./useApiCall-B0EBj9IB.js";var k=b(`ban`,[[`circle`,{cx:`12`,cy:`12`,r:`10`,key:`1mglay`}],[`path`,{d:`M4.929 4.929 19.07 19.071`,key:`196cmz`}]]),A=e(n(),1),j=t(),M=[[`ASSOCIATION`,`Association`],[`POLITICAL_ACHIEVEMENT`,`Political Achievement`],[`IMAGE`,`Image / Video`],[`DIRECTORY`,`Directory`],[`LETTER`,`Letter`]],N=12,P=e=>T(e?.imageUrl||e?.url||e?.secure_url||e?.image?.url||e?.image?.secure_url||``),F=e=>e?.id||e?._id,I=e=>M.find(([t])=>t===e)?.[1]||e||`Unknown`,L=e=>{if(!e)return`—`;let t=new Date(e);return Number.isNaN(t.getTime())?`—`:t.toLocaleDateString(`en-IN`,{day:`2-digit`,month:`short`,year:`numeric`})},R=e=>e?.isActive===!1,z=[{value:`IMAGE`,label:`Image`,accept:`image/*`,mime:`image/`,formats:`JPG, PNG, WEBP and other images`},{value:`VIDEO`,label:`Video`,accept:`video/*`,mime:`video/`,formats:`MP4, WEBM and other videos`}],B=e=>e?.resourceType===`VIDEO`||!e?.resourceType&&d(P(e))?`VIDEO`:`IMAGE`;function V({image:e,className:t=``}){return R(e)?(0,j.jsxs)(`span`,{className:`inline-flex items-center gap-1 rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-600 ${t}`,children:[(0,j.jsx)(k,{className:`h-3 w-3`}),`Hidden`]}):null}function H({image:e,onClick:t,withLabel:n=!1}){let r=R(e),a=r?`Unblock`:`Block`;return(0,j.jsxs)(`button`,{type:`button`,onClick:t,title:a,"aria-label":a,className:`inline-flex items-center justify-center gap-1.5 rounded-lg transition-all ${n?`h-8 border px-3 text-xs font-bold`:`h-8 w-8`} ${r?`border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100`:`border-red-200 bg-red-50 text-red-600 hover:bg-red-100`}`,children:[r?(0,j.jsx)(i,{className:`h-4 w-4`}):(0,j.jsx)(k,{className:`h-4 w-4`}),n&&a]})}function U({title:e,description:t,onClose:n,children:r,maxWidth:i=`max-w-2xl`}){return(0,A.useEffect)(()=>{let e=e=>{e.key===`Escape`&&n()};return document.addEventListener(`keydown`,e),()=>{document.removeEventListener(`keydown`,e)}},[n]),(0,j.jsx)(`div`,{className:`
        fixed inset-0 z-[100]
        flex items-center justify-center
        bg-slate-950/65
        p-3 sm:p-5
        backdrop-blur-[3px]
      `,role:`dialog`,"aria-modal":`true`,onClick:n,children:(0,j.jsxs)(`div`,{className:`
          ${i}
          w-full
          max-h-[94vh]
          overflow-hidden
          rounded-xl
          border border-slate-200
          bg-white
          shadow-2xl
          animate-in
          fade-in-0
          zoom-in-95
          duration-150
        `,onClick:e=>e.stopPropagation(),children:[(0,j.jsxs)(`div`,{className:`
            flex items-start justify-between
            gap-4
            border-b border-slate-200
            bg-slate-50
            px-4 py-4
            sm:px-6
          `,children:[(0,j.jsx)(`div`,{className:`min-w-0`,children:(0,j.jsxs)(`div`,{className:`flex items-center gap-2`,children:[(0,j.jsx)(`div`,{className:`
                  flex h-9 w-9 shrink-0
                  items-center justify-center
                  rounded-lg
                  bg-blue-50
                  text-blue-700
                `,children:(0,j.jsx)(y,{className:`h-4 w-4`})}),(0,j.jsxs)(`div`,{className:`min-w-0`,children:[(0,j.jsx)(`h3`,{className:`truncate text-sm font-bold text-slate-900 sm:text-base`,children:e}),t&&(0,j.jsx)(`p`,{className:`mt-0.5 text-xs text-slate-500`,children:t})]})]})}),(0,j.jsx)(`button`,{type:`button`,onClick:n,className:`
              flex h-8 w-8 shrink-0
              items-center justify-center
              rounded-lg
              text-slate-400
              transition-all
              hover:bg-red-50
              hover:text-red-700
              focus:outline-none
              focus:ring-2
              focus:ring-red-200
            `,"aria-label":`Close`,children:(0,j.jsx)(v,{className:`h-4 w-4`})})]}),(0,j.jsx)(`div`,{className:`max-h-[calc(94vh-80px)] overflow-y-auto p-4 sm:p-6`,children:r})]})})}function te({image:e,onClose:t}){if(!e)return null;let n=P(e),r=B(e)===`VIDEO`;return(0,j.jsxs)(U,{title:e.title||`Gallery Preview`,description:`${I(e.category)} • ${L(e.createdAt)}`,onClose:t,maxWidth:`max-w-4xl`,children:[(0,j.jsx)(`div`,{className:`overflow-hidden rounded-xl border border-slate-200 bg-slate-950`,children:r?(0,j.jsx)(p,{url:n,autoPlay:!0,className:`max-h-[68vh] w-full bg-black object-contain`}):(0,j.jsx)(`img`,{src:n,alt:e.title||`Gallery`,className:`
              max-h-[68vh]
              w-full
              object-contain
              bg-slate-950
            `})}),e.description&&(0,j.jsxs)(`div`,{className:`mt-4 rounded-xl border border-slate-200 bg-slate-50 p-4`,children:[(0,j.jsx)(`p`,{className:`text-[11px] font-bold uppercase tracking-wide text-slate-400`,children:`Description`}),(0,j.jsx)(`p`,{className:`mt-2 whitespace-pre-wrap text-sm leading-6 text-slate-700`,children:e.description})]}),(0,j.jsxs)(`div`,{className:`mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2`,children:[(0,j.jsxs)(`div`,{className:`rounded-lg border border-slate-200 bg-slate-50 p-3`,children:[(0,j.jsx)(`p`,{className:`text-[11px] font-bold uppercase tracking-wide text-slate-400`,children:`Category`}),(0,j.jsx)(`p`,{className:`mt-1 text-sm font-semibold text-slate-800`,children:I(e.category)})]}),(0,j.jsxs)(`div`,{className:`rounded-lg border border-slate-200 bg-slate-50 p-3`,children:[(0,j.jsx)(`p`,{className:`text-[11px] font-bold uppercase tracking-wide text-slate-400`,children:`Uploaded`}),(0,j.jsx)(`p`,{className:`mt-1 text-sm font-semibold text-slate-800`,children:L(e.createdAt)})]})]}),(0,j.jsx)(`div`,{className:`mt-4 flex justify-end`,children:(0,j.jsx)(`button`,{type:`button`,onClick:t,className:`
            inline-flex h-10
            items-center justify-center
            rounded-lg
            border border-slate-200
            bg-white
            px-5
            text-sm font-semibold
            text-slate-600
            transition-all
            hover:bg-slate-100
          `,children:`Close`})})]})}function ne({image:e,onClose:t,onSaved:n}){let i=D(S),a=D(w),[o,d]=(0,A.useState)(e?.category||`ASSOCIATION`),[f,m]=(0,A.useState)(e?.description||``),[h,g]=(0,A.useState)(e?B(e):`IMAGE`),[v,y]=(0,A.useState)(null),[b,x]=(0,A.useState)(e&&B(e)===`VIDEO`&&e.imageUrl||``),[C,T]=(0,A.useState)(P(e)),E=h===`VIDEO`,O=/^https?:\/\/\S+$/i.test(b.trim()),k=z.find(e=>e.value===h),N=e=>{e!==h&&(g(e),y(null),x(``),V(``))},[I,L]=(0,A.useState)(!1),[R,V]=(0,A.useState)(``);(0,A.useEffect)(()=>{if(!v){T(P(e));return}let t=URL.createObjectURL(v);return T(t),()=>{URL.revokeObjectURL(t)}},[v,e]);let H=E,U=E?b.trim():C;return(0,j.jsxs)(`form`,{onSubmit:async t=>{if(t.preventDefault(),E&&!O){let e=`Please enter a valid video URL (starting with http:// or https://).`;V(e),_.error(e);return}if(!E&&!e&&!v){let e=`Please select an image.`;V(e),_.error(e);return}L(!0),V(``);try{e?(await i({id:F(e),file:E?null:v,videoUrl:E?b.trim():void 0,category:o,description:f.trim()}),_.success(`Gallery media updated successfully.`)):(await a({file:E?null:v,videoUrl:E?b.trim():void 0,category:o,description:f.trim(),resourceType:h}),_.success(`${k.label} uploaded successfully.`)),await n()}catch(e){let t=e?.message||`Unable to save the gallery media.`;V(t),_.error(t)}finally{L(!1)}},className:`space-y-5`,children:[!e&&(0,j.jsx)(`div`,{role:`tablist`,"aria-label":`Media type`,className:`grid grid-cols-2 gap-1 rounded-lg bg-slate-100 p-1`,children:z.map(e=>(0,j.jsx)(`button`,{type:`button`,role:`tab`,"aria-selected":h===e.value,onClick:()=>N(e.value),className:`h-9 rounded-md text-sm font-semibold transition-all ${h===e.value?`bg-white text-slate-900 shadow-sm`:`text-slate-500 hover:text-slate-700`}`,children:e.label},e.value))}),(0,j.jsxs)(`div`,{children:[(0,j.jsxs)(`label`,{htmlFor:`gallery-category`,className:`mb-1.5 block text-xs font-bold text-slate-700 sm:text-sm`,children:[`Category`,(0,j.jsx)(`span`,{className:`ml-1 text-red-600`,children:`*`})]}),(0,j.jsx)(`select`,{id:`gallery-category`,value:o,onChange:e=>d(e.target.value),className:`
            ${r}
            mt-0
            h-11
            w-full
            rounded-lg
            border-slate-300
            bg-white
            px-3
            text-sm
            text-slate-700
            transition-all
            focus:border-blue-600
            focus:ring-2
            focus:ring-blue-100
          `,children:M.map(([e,t])=>(0,j.jsx)(`option`,{value:e,children:t},e))})]}),(0,j.jsxs)(`div`,{children:[(0,j.jsxs)(`label`,{htmlFor:`gallery-description`,className:`mb-1.5 block text-xs font-bold text-slate-700 sm:text-sm`,children:[`Description`,(0,j.jsx)(`span`,{className:`ml-1 text-red-600`,children:`*`})]}),(0,j.jsx)(`textarea`,{id:`gallery-description`,value:f,onChange:e=>m(e.target.value),placeholder:`Enter ${k.label.toLowerCase()} description...`,rows:4,required:!0,className:`
            w-full
            resize-none
            rounded-lg
            border
            border-slate-300
            bg-white
            px-3
            py-2.5
            text-sm
            text-slate-700
            outline-none
            transition-all
            placeholder:text-slate-400
            hover:border-slate-400
            focus:border-blue-600
            focus:ring-2
            focus:ring-blue-100
          `}),(0,j.jsx)(`p`,{className:`mt-1 text-[11px] text-slate-400`,children:`Add a short description for this gallery media.`})]}),E?(0,j.jsxs)(`div`,{children:[(0,j.jsxs)(`label`,{htmlFor:`gallery-video-url`,className:`mb-1.5 block text-xs font-bold text-slate-700 sm:text-sm`,children:[`Video URL`,(0,j.jsx)(`span`,{className:`ml-1 text-red-600`,children:`*`})]}),(0,j.jsxs)(`div`,{className:`relative`,children:[(0,j.jsx)(c,{className:`pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400`}),(0,j.jsx)(`input`,{id:`gallery-video-url`,type:`url`,value:b,onChange:e=>x(e.target.value),placeholder:`https://www.youtube.com/watch?v=...`,required:!0,className:`h-11 w-full rounded-lg border border-slate-300 bg-white pl-9 pr-3 text-sm text-slate-700 outline-none transition-all placeholder:text-slate-400 hover:border-slate-400 focus:border-blue-600 focus:ring-2 focus:ring-blue-100`})]}),(0,j.jsx)(`p`,{className:`mt-1 text-[11px] text-slate-400`,children:`Paste a YouTube, Vimeo or direct video file (.mp4, .webm) link.`})]}):(0,j.jsxs)(`div`,{children:[(0,j.jsxs)(`label`,{htmlFor:`gallery-file`,className:`mb-1.5 block text-xs font-bold text-slate-700 sm:text-sm`,children:[e?`Replace ${k.label.toLowerCase()}`:k.label,!e&&(0,j.jsx)(`span`,{className:`ml-1 text-red-600`,children:`*`})]}),(0,j.jsxs)(`label`,{htmlFor:`gallery-file`,className:`
              flex min-h-12
              cursor-pointer
              items-center
              gap-3
              rounded-lg
              border border-dashed
              border-slate-300
              bg-slate-50
              px-3
              transition-all
              hover:border-blue-400
              hover:bg-blue-50/40
            `,children:[(0,j.jsx)(`div`,{className:`
                flex h-8 w-8 shrink-0
                items-center justify-center
                rounded-lg
                bg-blue-100
                text-blue-700
              `,children:(0,j.jsx)(s,{className:`h-4 w-4`})}),(0,j.jsxs)(`div`,{className:`min-w-0 flex-1`,children:[(0,j.jsx)(`p`,{className:`truncate text-xs font-semibold text-slate-700 sm:text-sm`,children:v?v.name:`Choose ${k.label.toLowerCase()}`}),(0,j.jsx)(`p`,{className:`text-[11px] text-slate-400`,children:k.formats})]}),(0,j.jsx)(`span`,{className:`
                hidden shrink-0
                rounded-md
                border border-slate-200
                bg-white
                px-3 py-1.5
                text-xs font-semibold
                text-slate-600
                sm:block
              `,children:`Browse`}),(0,j.jsx)(`input`,{id:`gallery-file`,type:`file`,accept:k.accept,required:!e,onChange:e=>{let t=e.target.files?.[0];if(!t){y(null);return}if(!t.type.startsWith(k.mime)){V(`Please select a valid ${k.label.toLowerCase()} file.`),e.target.value=``;return}V(``),y(t)},className:`hidden`})]})]}),U&&(0,j.jsxs)(`div`,{children:[(0,j.jsxs)(`div`,{className:`mb-2 flex items-center justify-between`,children:[(0,j.jsx)(`p`,{className:`text-xs font-bold text-slate-700 sm:text-sm`,children:`Preview`}),H&&(0,j.jsxs)(`span`,{className:`
                  inline-flex items-center gap-1
                  rounded-full
                  bg-slate-100
                  px-2.5 py-1
                  text-[11px]
                  font-semibold
                  text-slate-600
                `,children:[(0,j.jsx)(u,{className:`h-3 w-3 fill-current`}),`Video`]})]}),(0,j.jsx)(`div`,{className:`
              overflow-hidden
              rounded-xl
              border border-slate-200
              bg-slate-950
              shadow-sm
            `,children:H?(0,j.jsx)(p,{url:U,muted:!0,className:`h-56 w-full object-contain sm:h-72`}):(0,j.jsx)(`img`,{src:C,alt:`Gallery preview`,className:`
                  h-56
                  w-full
                  object-contain
                  sm:h-72
                `})})]}),R&&(0,j.jsx)(`div`,{role:`alert`,className:`
            rounded-lg
            border border-red-200
            bg-red-50
            px-3 py-2.5
            text-xs font-medium
            text-red-700
          `,children:R}),(0,j.jsxs)(`div`,{className:`
          flex flex-col-reverse
          gap-2
          border-t border-slate-200
          pt-4
          sm:flex-row
          sm:justify-end
        `,children:[(0,j.jsx)(`button`,{type:`button`,onClick:t,disabled:I,className:`
            h-10
            rounded-lg
            border border-slate-200
            bg-white
            px-5
            text-sm font-semibold
            text-slate-600
            transition-all
            hover:bg-slate-100
            disabled:cursor-not-allowed
            disabled:opacity-50
          `,children:`Cancel`}),(0,j.jsxs)(`button`,{type:`submit`,disabled:I||!f.trim()||(E?!O:!e&&!v),className:`
            inline-flex
            h-10
            items-center
            justify-center
            gap-2
            rounded-lg
            bg-[#b00000]
            px-5
            text-sm font-semibold
            text-white
            shadow-sm
            transition-all
            hover:bg-[#8f0000]
            focus:outline-none
            focus:ring-2
            focus:ring-red-200
            disabled:cursor-not-allowed
            disabled:opacity-60
          `,children:[I?(0,j.jsx)(ee,{className:`h-4 w-4 animate-spin`}):e?(0,j.jsx)(l,{className:`h-4 w-4`}):(0,j.jsx)(s,{className:`h-4 w-4`}),I?e?`Saving...`:`Uploading...`:e?`Save changes`:E?`Add video`:`Upload image`]})]})]})}function re(){return(0,j.jsxs)(`div`,{className:`
        flex items-center gap-3
        rounded-xl
        border border-slate-200
        bg-white
        p-3
      `,children:[(0,j.jsx)(E,{className:`h-16 w-16 shrink-0 rounded-lg`}),(0,j.jsxs)(`div`,{className:`min-w-0 flex-1 space-y-2`,children:[(0,j.jsx)(E,{className:`h-4 w-28 rounded-full`}),(0,j.jsx)(E,{className:`h-3 w-20`})]}),(0,j.jsx)(E,{className:`h-8 w-14 rounded-lg`})]})}function ie({rows:e=6}){return(0,j.jsx)(`div`,{className:`hidden overflow-hidden rounded-xl border border-slate-200 bg-white md:block`,children:(0,j.jsxs)(`table`,{className:`w-full text-left text-sm`,children:[(0,j.jsx)(`thead`,{className:`bg-slate-50`,children:(0,j.jsxs)(`tr`,{children:[(0,j.jsx)(`th`,{className:`px-4 py-3`,children:(0,j.jsx)(E,{className:`h-3 w-16`})}),(0,j.jsx)(`th`,{className:`px-4 py-3`,children:(0,j.jsx)(E,{className:`h-3 w-20`})}),(0,j.jsx)(`th`,{className:`px-4 py-3`,children:(0,j.jsx)(E,{className:`h-3 w-16`})}),(0,j.jsx)(`th`,{className:`px-4 py-3`,children:(0,j.jsx)(E,{className:`h-3 w-32`})}),(0,j.jsx)(`th`,{className:`px-4 py-3 text-right`,children:(0,j.jsx)(E,{className:`ml-auto h-3 w-20`})})]})}),(0,j.jsx)(`tbody`,{children:Array.from({length:e}).map((e,t)=>(0,j.jsxs)(`tr`,{className:`border-t border-slate-100`,children:[(0,j.jsx)(`td`,{className:`px-4 py-3`,children:(0,j.jsx)(E,{className:`h-14 w-14 rounded-lg`})}),(0,j.jsx)(`td`,{className:`px-4 py-3`,children:(0,j.jsx)(E,{className:`h-6 w-28 rounded-full`})}),(0,j.jsx)(`td`,{className:`px-4 py-3`,children:(0,j.jsx)(E,{className:`h-4 w-24`})}),(0,j.jsx)(`td`,{className:`px-4 py-3`,children:(0,j.jsx)(E,{className:`h-4 w-40`})}),(0,j.jsx)(`td`,{className:`px-4 py-3`,children:(0,j.jsxs)(`div`,{className:`flex justify-end gap-2`,children:[(0,j.jsx)(E,{className:`h-8 w-16 rounded-lg`}),(0,j.jsx)(E,{className:`h-8 w-16 rounded-lg`})]})})]},t))})]})})}function W({image:e,size:t=`normal`}){let n=P(e),r=B(e)===`VIDEO`,i=r?f(n):``;return(0,j.jsxs)(`div`,{className:`
        ${t===`small`?`h-14 w-14`:`h-16 w-16`}
        relative
        shrink-0
        overflow-hidden
        rounded-lg
        border border-slate-200
        bg-slate-100
      `,children:[n?r&&!i?(0,j.jsx)(`video`,{src:n,muted:!0,preload:`metadata`,className:`h-full w-full object-cover`}):(0,j.jsx)(`img`,{src:i||n,alt:e?.title||`Gallery`,className:`h-full w-full object-cover`}):(0,j.jsx)(`div`,{className:`flex h-full w-full items-center justify-center`,children:(0,j.jsx)(o,{className:`h-5 w-5 text-slate-300`})}),r&&(0,j.jsx)(`span`,{className:`
            absolute inset-0
            flex items-center justify-center
            bg-black/25
          `,children:(0,j.jsx)(`span`,{className:`
              flex h-7 w-7
              items-center justify-center
              rounded-full
              bg-black/55
              text-white
            `,children:(0,j.jsx)(u,{className:`h-3.5 w-3.5 fill-white`})})})]})}function G(){let e=O(C),t=D(x),[n,r]=(0,A.useState)([]),[s,c]=(0,A.useState)(!0),[u,d]=(0,A.useState)(``),[f,v]=(0,A.useState)(``),[y,b]=(0,A.useState)(``),[S,w]=(0,A.useState)(1),[T,E]=(0,A.useState)(void 0),[z,G]=(0,A.useState)(null),[K,q]=(0,A.useState)(null),[J,Y]=(0,A.useState)(!1),X=(0,A.useCallback)(async()=>{c(!0),d(``);try{let t=await e({category:f,admin:!0});r((Array.isArray(t?.gallery)?t.gallery:[]).filter(e=>e?.category!==`BANNER`))}catch(e){let t=e?.message||`Could not load gallery images.`;d(t),_.error(t),r([])}finally{c(!1)}},[f,e]);(0,A.useEffect)(()=>{X()},[X]);let Z=n.filter(e=>[e?.title,e?.description,e?.category].filter(Boolean).join(` `).toLowerCase().includes(y.trim().toLowerCase())),Q=Math.max(1,Math.ceil(Z.length/N)),$=Math.min(S,Q),ae=Z.slice(($-1)*N,$*N);return(0,A.useEffect)(()=>{w(1)},[f,y]),(0,j.jsxs)(`section`,{className:`min-h-full space-y-4`,children:[(0,j.jsxs)(`div`,{className:`
          rounded-xl
          border border-slate-200
          bg-white
          p-4
          shadow-sm
          sm:p-5
        `,children:[(0,j.jsxs)(`div`,{className:`
            flex flex-col
            gap-4
            sm:flex-row
            sm:items-center
            sm:justify-between
          `,children:[(0,j.jsxs)(`div`,{className:`flex min-w-0 items-center gap-3`,children:[(0,j.jsx)(`div`,{className:`
                flex h-11 w-11
                shrink-0
                items-center justify-center
                rounded-xl
                bg-blue-50
                text-blue-700
              `,children:(0,j.jsx)(o,{className:`h-5 w-5`})}),(0,j.jsxs)(`div`,{className:`min-w-0`,children:[(0,j.jsx)(`h2`,{className:`text-lg font-bold text-slate-900 sm:text-xl`,children:`Media Library`}),(0,j.jsx)(`p`,{className:`mt-0.5 text-xs text-slate-500 sm:text-sm`,children:`Manage images and videos shown on the public site.`})]})]}),(0,j.jsxs)(`button`,{type:`button`,onClick:()=>E(null),className:`
              inline-flex
              h-10
              w-full
              items-center
              justify-center
              gap-2
              rounded-lg
              bg-[#b00000]
              px-4
              text-sm
              font-bold
              text-white
              shadow-sm
              transition-all
              hover:bg-[#8f0000]
              hover:shadow-md
              focus:outline-none
              focus:ring-2
              focus:ring-red-200
              sm:w-auto
            `,children:[(0,j.jsx)(m,{className:`h-4 w-4`}),`Add Media`]})]}),(0,j.jsxs)(`div`,{className:`mt-4 flex flex-wrap gap-2`,children:[(0,j.jsxs)(`div`,{className:`
              inline-flex items-center gap-2
              rounded-full
              bg-blue-50
              px-3 py-1.5
              text-xs
              font-semibold
              text-blue-700
            `,children:[(0,j.jsx)(`span`,{className:`h-1.5 w-1.5 rounded-full bg-blue-600`}),Z.length,` total media`]}),f&&(0,j.jsx)(`div`,{className:`
                inline-flex items-center
                rounded-full
                bg-slate-100
                px-3 py-1.5
                text-xs
                font-semibold
                text-slate-600
              `,children:I(f)})]})]}),(0,j.jsxs)(`div`,{className:`
          rounded-xl
          border border-slate-200
          bg-white
          p-3
          shadow-sm
          sm:p-4
        `,children:[(0,j.jsxs)(`div`,{className:`grid grid-cols-1 gap-3 md:grid-cols-[auto_1fr]`,children:[(0,j.jsxs)(`div`,{children:[(0,j.jsx)(`label`,{htmlFor:`gallery-filter`,className:`
                mb-1.5
                block
                text-xs
                font-bold
                text-slate-600
              `,children:`Category`}),(0,j.jsxs)(`select`,{id:`gallery-filter`,value:f,onChange:e=>v(e.target.value),className:`
                h-10
                w-full
                min-w-[190px]
                rounded-lg
                border
                border-slate-300
                bg-white
                px-3
                text-sm
                text-slate-700
                outline-none
                transition-all
                hover:border-slate-400
                focus:border-blue-600
                focus:ring-2
                focus:ring-blue-100
              `,children:[(0,j.jsx)(`option`,{value:``,children:`All categories`}),M.map(([e,t])=>(0,j.jsx)(`option`,{value:e,children:t},e))]})]}),(0,j.jsxs)(`div`,{children:[(0,j.jsx)(`label`,{htmlFor:`gallery-search`,className:`
                mb-1.5
                block
                text-xs
                font-bold
                text-slate-600
              `,children:`Search`}),(0,j.jsxs)(`div`,{className:`relative`,children:[(0,j.jsx)(h,{className:`
                  pointer-events-none
                  absolute
                  left-3
                  top-1/2
                  h-4
                  w-4
                  -translate-y-1/2
                  text-slate-400
                `}),(0,j.jsx)(`input`,{id:`gallery-search`,value:y,onChange:e=>b(e.target.value),placeholder:`Search gallery...`,className:`
                  h-10
                  w-full
                  rounded-lg
                  border
                  border-slate-300
                  bg-white
                  py-2
                  pl-9
                  pr-3
                  text-sm
                  text-slate-700
                  outline-none
                  transition-all
                  placeholder:text-slate-400
                  hover:border-slate-400
                  focus:border-blue-600
                  focus:ring-2
                  focus:ring-blue-100
                `})]})]})]}),u&&(0,j.jsx)(`div`,{role:`alert`,className:`
              mt-3
              rounded-lg
              border border-red-200
              bg-red-50
              px-3 py-2.5
              text-xs
              font-medium
              text-red-700
            `,children:u}),s?(0,j.jsxs)(j.Fragment,{children:[(0,j.jsx)(`div`,{className:`mt-4 space-y-2 md:hidden`,children:Array.from({length:5}).map((e,t)=>(0,j.jsx)(re,{},t))}),(0,j.jsx)(`div`,{className:`mt-4`,children:(0,j.jsx)(ie,{})})]}):Z.length===0?(0,j.jsxs)(`div`,{className:`
              mt-4
              flex
              min-h-[260px]
              flex-col
              items-center
              justify-center
              rounded-xl
              border
              border-dashed
              border-slate-300
              bg-slate-50/60
              px-4
              text-center
            `,children:[(0,j.jsx)(`div`,{className:`
                flex h-14 w-14
                items-center justify-center
                rounded-full
                bg-white
                shadow-sm
              `,children:(0,j.jsx)(o,{className:`h-6 w-6 text-slate-300`})}),(0,j.jsx)(`p`,{className:`mt-4 text-sm font-bold text-slate-600`,children:`No images found`}),(0,j.jsx)(`p`,{className:`mt-1 max-w-sm text-xs text-slate-400`,children:`Try changing the category or search keyword.`})]}):(0,j.jsxs)(j.Fragment,{children:[(0,j.jsx)(`div`,{className:`mt-4 space-y-2 md:hidden`,children:ae.map(e=>{let t=F(e);return(0,j.jsxs)(`div`,{className:`
                      group
                      rounded-xl
                      border
                      border-slate-200
                      bg-white
                      p-3
                      shadow-sm
                      transition-all
                      hover:border-blue-200
                      hover:shadow-md
                    `,children:[(0,j.jsxs)(`div`,{className:`flex items-center gap-3`,children:[(0,j.jsx)(W,{image:e,size:`small`}),(0,j.jsxs)(`div`,{className:`min-w-0 flex-1`,children:[(0,j.jsx)(`span`,{className:`
                            inline-flex
                            max-w-full
                            rounded-full
                            bg-blue-50
                            px-2.5 py-1
                            text-[10px]
                            font-bold
                            text-blue-700
                          `,children:(0,j.jsx)(`span`,{className:`truncate`,children:I(e.category)})}),(0,j.jsxs)(`div`,{className:`mt-2 flex items-center gap-1.5 text-[11px] text-slate-400`,children:[(0,j.jsx)(g,{className:`h-3 w-3`}),L(e.createdAt),(0,j.jsx)(V,{image:e,className:`ml-1`})]})]}),(0,j.jsxs)(`div`,{className:`flex shrink-0 items-center gap-1`,children:[(0,j.jsx)(`button`,{type:`button`,onClick:()=>G(e),className:`
                            flex h-8 w-8
                            items-center justify-center
                            rounded-lg
                            bg-blue-50
                            text-blue-700
                            transition-all
                            hover:bg-blue-100
                          `,title:`View`,children:(0,j.jsx)(a,{className:`h-4 w-4`})}),(0,j.jsx)(`button`,{type:`button`,onClick:()=>E(e),className:`
                            flex h-8 w-8
                            items-center justify-center
                            rounded-lg
                            bg-slate-100
                            text-slate-600
                            transition-all
                            hover:bg-slate-200
                            hover:text-slate-900
                          `,title:`Edit`,children:(0,j.jsx)(l,{className:`h-4 w-4`})}),(0,j.jsx)(H,{image:e,onClick:()=>q(e)})]})]}),e.description&&(0,j.jsx)(`p`,{className:`
                          mt-3
                          border-t
                          border-slate-100
                          pt-3
                          text-xs
                          leading-5
                          text-slate-500
                          line-clamp-2
                        `,children:e.description})]},t)})}),(0,j.jsx)(`div`,{className:`
                mt-4
                hidden
                overflow-hidden
                rounded-xl
                border
                border-slate-200
                bg-white
                md:block
              `,children:(0,j.jsx)(`div`,{className:`overflow-x-auto`,children:(0,j.jsxs)(`table`,{className:`w-full min-w-[900px] text-left text-sm`,children:[(0,j.jsx)(`thead`,{children:(0,j.jsxs)(`tr`,{className:`
                        border-b
                        border-slate-200
                        bg-slate-50
                      `,children:[(0,j.jsx)(`th`,{className:`
                          px-4 py-3
                          text-[11px]
                          font-bold
                          uppercase
                          tracking-wider
                          text-slate-500
                        `,children:`Media`}),(0,j.jsx)(`th`,{className:`
                          px-4 py-3
                          text-[11px]
                          font-bold
                          uppercase
                          tracking-wider
                          text-slate-500
                        `,children:`Category`}),(0,j.jsx)(`th`,{className:`
                          px-4 py-3
                          text-[11px]
                          font-bold
                          uppercase
                          tracking-wider
                          text-slate-500
                        `,children:`Description`}),(0,j.jsx)(`th`,{className:`
                          px-4 py-3
                          text-[11px]
                          font-bold
                          uppercase
                          tracking-wider
                          text-slate-500
                        `,children:`Uploaded`}),(0,j.jsx)(`th`,{className:`
                          px-4 py-3
                          text-center
                          text-[11px]
                          font-bold
                          uppercase
                          tracking-wider
                          text-slate-500
                        `,children:`Actions`})]})}),(0,j.jsx)(`tbody`,{children:ae.map(e=>{let t=F(e);return(0,j.jsxs)(`tr`,{className:`
                            border-b
                            border-slate-100
                            transition-colors
                            last:border-b-0
                            hover:bg-blue-50/40
                          `,children:[(0,j.jsx)(`td`,{className:`px-4 py-3`,children:(0,j.jsxs)(`div`,{className:`flex items-center gap-3`,children:[(0,j.jsx)(W,{image:e}),(0,j.jsxs)(`div`,{className:`hidden lg:block`,children:[(0,j.jsx)(`p`,{className:`max-w-[200px] truncate text-sm font-semibold text-slate-800`,children:e.title||`Gallery media`}),(0,j.jsx)(`p`,{className:`mt-0.5 text-xs text-slate-400`,children:B(e)===`VIDEO`?`Video`:`Image`})]})]})}),(0,j.jsxs)(`td`,{className:`px-4 py-3`,children:[(0,j.jsx)(`span`,{className:`
                                inline-flex
                                items-center
                                rounded-full
                                bg-blue-50
                                px-2.5 py-1
                                text-[11px]
                                font-bold
                                text-blue-700
                              `,children:I(e.category)}),(0,j.jsx)(V,{image:e,className:`ml-2`})]}),(0,j.jsx)(`td`,{className:`px-4 py-3`,children:(0,j.jsx)(`p`,{className:`
                                max-w-[300px]
                                truncate
                                text-xs
                                leading-5
                                text-slate-500
                              `,title:e.description||``,children:e.description||`—`})}),(0,j.jsx)(`td`,{className:`px-4 py-3`,children:(0,j.jsxs)(`div`,{className:`flex items-center gap-2 text-xs text-slate-500`,children:[(0,j.jsx)(g,{className:`h-3.5 w-3.5 text-slate-400`}),L(e.createdAt)]})}),(0,j.jsx)(`td`,{className:`px-4 py-3`,children:(0,j.jsxs)(`div`,{className:`flex justify-end gap-2`,children:[(0,j.jsxs)(`button`,{type:`button`,onClick:()=>G(e),className:`
                                  inline-flex
                                  h-8
                                  items-center
                                  gap-1.5
                                  rounded-lg
                                  border
                                  border-blue-200
                                  bg-blue-50
                                  px-3
                                  text-xs
                                  font-bold
                                  text-blue-700
                                  transition-all
                                  hover:border-blue-300
                                  hover:bg-blue-100
                                `,children:[(0,j.jsx)(a,{className:`h-3.5 w-3.5`}),`View`]}),(0,j.jsxs)(`button`,{type:`button`,onClick:()=>E(e),className:`
                                  inline-flex
                                  h-8
                                  items-center
                                  gap-1.5
                                  rounded-lg
                                  border
                                  border-slate-200
                                  bg-white
                                  px-3
                                  text-xs
                                  font-bold
                                  text-slate-600
                                  transition-all
                                  hover:border-slate-300
                                  hover:bg-slate-100
                                  hover:text-slate-900
                                `,children:[(0,j.jsx)(l,{className:`h-3.5 w-3.5`}),`Edit`]}),(0,j.jsx)(H,{image:e,withLabel:!0,onClick:()=>q(e)})]})})]},t)})})]})})})]}),!s&&Z.length>0&&(0,j.jsxs)(`div`,{className:`
              mt-4
              flex
              flex-col
              gap-3
              border-t
              border-slate-100
              pt-4
              sm:flex-row
              sm:items-center
              sm:justify-between
            `,children:[(0,j.jsxs)(`p`,{className:`text-xs text-slate-500`,children:[`Showing`,` `,(0,j.jsx)(`span`,{className:`font-bold text-slate-700`,children:($-1)*N+1}),` `,`-`,` `,(0,j.jsx)(`span`,{className:`font-bold text-slate-700`,children:Math.min($*N,Z.length)}),` `,`of `,(0,j.jsx)(`span`,{className:`font-bold text-slate-700`,children:Z.length})]}),Q>1&&(0,j.jsxs)(`div`,{className:`flex items-center justify-center gap-2`,children:[(0,j.jsx)(`button`,{type:`button`,onClick:()=>w(e=>Math.max(1,e-1)),disabled:$<=1,className:`
                    h-9
                    rounded-lg
                    border
                    border-slate-200
                    bg-white
                    px-3
                    text-xs
                    font-bold
                    text-slate-600
                    transition-all
                    hover:border-blue-200
                    hover:bg-blue-50
                    hover:text-blue-700
                    disabled:cursor-not-allowed
                    disabled:opacity-40
                  `,children:`Prev`}),(0,j.jsxs)(`span`,{className:`
                    inline-flex
                    h-9
                    items-center
                    rounded-lg
                    bg-slate-900
                    px-3
                    text-xs
                    font-bold
                    text-white
                  `,children:[$,` / `,Q]}),(0,j.jsx)(`button`,{type:`button`,onClick:()=>w(e=>Math.min(Q,e+1)),disabled:$>=Q,className:`
                    h-9
                    rounded-lg
                    border
                    border-slate-200
                    bg-white
                    px-3
                    text-xs
                    font-bold
                    text-slate-600
                    transition-all
                    hover:border-blue-200
                    hover:bg-blue-50
                    hover:text-blue-700
                    disabled:cursor-not-allowed
                    disabled:opacity-40
                  `,children:`Next`})]})]})]}),z&&(0,j.jsx)(te,{image:z,onClose:()=>G(null)}),T!==void 0&&(0,j.jsx)(U,{title:T?`Edit Gallery Media`:`Upload Gallery Media`,description:T?`Update the category, description or replace the current media.`:`Choose a category, add a description and upload an image or add a video link.`,onClose:()=>E(void 0),maxWidth:`max-w-2xl`,children:(0,j.jsx)(ne,{image:T,onClose:()=>E(void 0),onSaved:async()=>{E(void 0),await X()}})}),K&&(0,j.jsx)(U,{title:R(K)?`Unblock this media?`:`Block this media?`,description:R(K)?`It will be visible on the public site again.`:`It will be hidden from the public site. You can unblock it anytime.`,onClose:()=>!J&&q(null),maxWidth:`max-w-md`,children:(0,j.jsxs)(`div`,{className:`space-y-5`,children:[(0,j.jsx)(`div`,{className:`overflow-hidden rounded-xl border border-slate-200 bg-slate-950`,children:B(K)===`VIDEO`?(0,j.jsx)(p,{url:P(K),muted:!0,className:`h-40 w-full object-contain`}):(0,j.jsx)(`img`,{src:P(K),alt:K.title||`Gallery media`,className:`h-40 w-full object-contain`})}),(0,j.jsxs)(`div`,{className:`flex flex-col-reverse gap-2 sm:flex-row sm:justify-end`,children:[(0,j.jsx)(`button`,{type:`button`,onClick:()=>q(null),disabled:J,className:`h-10 rounded-lg border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-600 transition-all hover:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-50`,children:`Cancel`}),(0,j.jsxs)(`button`,{type:`button`,onClick:async()=>{if(!K)return;let e=F(K),n=R(K);Y(!0);try{await t({id:e,isActive:n}),_.success(n?`Media is now visible on the public site.`:`Media is now hidden from the public site.`),q(null),await X()}catch(e){_.error(e?.message||`Unable to update media visibility.`)}finally{Y(!1)}},disabled:J,className:`inline-flex h-10 items-center justify-center gap-2 rounded-lg px-5 text-sm font-bold text-white transition-all disabled:cursor-not-allowed disabled:opacity-60 ${R(K)?`bg-emerald-600 hover:bg-emerald-700`:`bg-red-600 hover:bg-red-700`}`,children:[J?(0,j.jsx)(ee,{className:`h-4 w-4 animate-spin`}):R(K)?(0,j.jsx)(i,{className:`h-4 w-4`}):(0,j.jsx)(k,{className:`h-4 w-4`}),R(K)?`Unblock`:`Block`]})]})]})})]})}export{G as GalleryManagement};