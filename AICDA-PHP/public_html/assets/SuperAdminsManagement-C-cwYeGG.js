import{o as e,t}from"./jsx-runtime-DVpe7PXp.js";import{t as n}from"./react-Dps3yNUO.js";import{a as r}from"./directory-shared-CtjPvLUJ.js";import{t as i}from"./circle-check-CIPqaBlg.js";import{t as a}from"./eye-off-CeOihroQ.js";import{t as o}from"./eye-DGZnmR0v.js";import{t as s}from"./loader-circle-DT9DtKTa.js";import{t as c}from"./mail-CJm3AGNd.js";import{t as l}from"./plus-DEhSC2aD.js";import{t as u}from"./triangle-alert-BSVB-MQo.js";import{t as d}from"./user-round-lY56mQPa.js";import{t as f}from"./users-CBcTV40S.js";import{a as p,c as m,d as h,i as g,l as _,n as v,r as y,t as b,u as x,v as S}from"./index-CXECB6AG.js";import{n as C,t as w}from"./useApiCall-B0EBj9IB.js";var T=S(`circle-x`,[[`circle`,{cx:`12`,cy:`12`,r:`10`,key:`1mglay`}],[`path`,{d:`m15 9-6 6`,key:`1uzhvr`}],[`path`,{d:`m9 9 6 6`,key:`z0biqf`}]]),E=S(`lock-keyhole`,[[`circle`,{cx:`12`,cy:`16`,r:`1`,key:`1au0dj`}],[`rect`,{x:`3`,y:`10`,width:`18`,height:`12`,rx:`2`,key:`6s8ecr`}],[`path`,{d:`M7 10V7a5 5 0 0 1 10 0v3`,key:`1pqi11`}]]),D=e(n(),1),O=t();function k({adminId:e}){let t=C(g),[n,r]=(0,D.useState)(null),[i,c]=(0,D.useState)(!1),l=async()=>{if(n!==null){r(null);return}c(!0);try{r((await t(e))?.password??``)}catch(e){m.error(e?.message||`Could not load password.`)}finally{c(!1)}},u=n!==null;return(0,O.jsxs)(`div`,{className:`flex items-center gap-2`,children:[(0,O.jsx)(`span`,{className:`max-w-[160px] truncate font-mono text-sm text-slate-700`,children:u?n:`••••••••`}),(0,O.jsx)(`button`,{type:`button`,onClick:l,disabled:i,"aria-label":u?`Hide password`:`Show password`,className:`
          flex h-8 w-8 shrink-0 items-center justify-center
          rounded-lg text-slate-500 transition-colors
          hover:bg-red-50 hover:text-red-700
          disabled:cursor-not-allowed disabled:opacity-50
        `,children:i?(0,O.jsx)(s,{className:`h-4 w-4 animate-spin`}):u?(0,O.jsx)(a,{className:`h-4 w-4`}):(0,O.jsx)(o,{className:`h-4 w-4`})})]})}function A(){let e=w(y),t=C(v),n=C(p),[a,o]=(0,D.useState)(!1),[g,S]=(0,D.useState)([]),[A,j]=(0,D.useState)(!0),[M,N]=(0,D.useState)(!1),[P,F]=(0,D.useState)(null),[I,L]=(0,D.useState)(null),[R,z]=(0,D.useState)(!1),B=async()=>{j(!0);try{S(await e()||[])}catch(e){m.error(e?.message||`Could not load Super Admins.`)}finally{j(!1)}};(0,D.useEffect)(()=>{B()},[]);let V=async e=>{e.preventDefault();let n=new FormData(e.currentTarget),r=n.get(`firstName`)?.toString().trim(),i=n.get(`lastName`)?.toString().trim(),a=n.get(`email`)?.toString().trim(),s=n.get(`phone`)?.toString().trim(),c=n.get(`password`)?.toString();N(!0);try{await t({firstName:r,lastName:i,email:a,phone:s,password:c}),m.success(`Super Admin created successfully.`),o(!1),await B()}catch(e){m.error(e?.message||`Could not create Super Admin.`)}finally{N(!1)}},H=e=>e?.id||e?._id,U=e=>e?.name||[e?.firstName,e?.lastName].filter(Boolean).join(` `)||`Unnamed Admin`,W=e=>e?.isActive??e?.active??e?.status===`ACTIVE`,G=e=>{L(e)};return(0,O.jsxs)(`section`,{className:`space-y-4`,children:[(0,O.jsx)(`div`,{className:`overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm`,children:(0,O.jsxs)(`div`,{className:`flex flex-col gap-4 p-4 sm:p-5 md:flex-row md:items-center md:justify-between`,children:[(0,O.jsxs)(`div`,{className:`flex items-center gap-3`,children:[(0,O.jsx)(`div`,{className:`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-red-50 text-red-700`,children:(0,O.jsx)(x,{className:`h-5 w-5`})}),(0,O.jsxs)(`div`,{children:[(0,O.jsx)(`h2`,{className:`text-base font-bold text-slate-900 sm:text-lg`,children:`Super Admins`}),(0,O.jsx)(`p`,{className:`mt-0.5 text-xs text-slate-500 sm:text-sm`,children:`Manage administrator accounts and access`})]})]}),(0,O.jsxs)(`button`,{type:`button`,onClick:()=>o(!0),className:`
              inline-flex h-10 w-full items-center
              justify-center gap-2 rounded-lg
              bg-red-700 px-4 text-sm font-semibold
              text-white shadow-sm transition-all
              hover:bg-red-800 hover:shadow-md
              active:scale-[0.98]
              sm:w-auto
            `,children:[(0,O.jsx)(l,{className:`h-4 w-4`}),`New Super Admin`]})]})}),(0,O.jsx)(`div`,{className:`overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm`,children:A?(0,O.jsxs)(`div`,{className:`flex min-h-64 flex-col items-center justify-center px-4`,children:[(0,O.jsx)(s,{className:`h-7 w-7 animate-spin text-red-700`}),(0,O.jsx)(`p`,{className:`mt-3 text-sm font-medium text-slate-500`,children:`Loading Super Admins...`})]}):g.length?(0,O.jsxs)(O.Fragment,{children:[(0,O.jsx)(`div`,{className:`space-y-3 p-3 md:hidden`,children:g.map(e=>{let t=W(e),n=U(e);return(0,O.jsxs)(`div`,{className:`
                      rounded-xl border border-slate-200
                      bg-white p-4 transition-all
                      hover:border-red-200 hover:shadow-sm
                    `,children:[(0,O.jsxs)(`div`,{className:`flex items-start justify-between gap-3`,children:[(0,O.jsxs)(`div`,{className:`flex min-w-0 items-center gap-3`,children:[(0,O.jsx)(`div`,{className:`
                            flex h-10 w-10 shrink-0
                            items-center justify-center
                            rounded-full bg-red-50 text-red-700
                          `,children:(0,O.jsx)(d,{className:`h-5 w-5`})}),(0,O.jsxs)(`div`,{className:`min-w-0`,children:[(0,O.jsx)(`p`,{className:`truncate text-sm font-bold text-slate-900`,children:n}),(0,O.jsxs)(`div`,{className:`
                              mt-1 flex min-w-0
                              items-center gap-1.5
                              text-xs text-slate-500
                            `,children:[(0,O.jsx)(c,{className:`h-3.5 w-3.5 shrink-0`}),(0,O.jsx)(`span`,{className:`truncate`,children:e.email||`—`})]})]})]}),(0,O.jsxs)(`button`,{type:`button`,onClick:()=>G(e),disabled:R,className:`
                          inline-flex shrink-0
                          items-center gap-1.5
                          rounded-full px-2.5 py-1
                          text-xs font-semibold
                          transition-all
                          disabled:cursor-not-allowed
                          disabled:opacity-60
                          ${t?`bg-emerald-50 text-emerald-700 hover:bg-emerald-100`:`bg-slate-100 text-slate-600 hover:bg-slate-200`}
                        `,children:[(0,O.jsx)(`span`,{className:`
                            h-1.5 w-1.5 rounded-full
                            ${t?`bg-emerald-500`:`bg-slate-400`}
                          `}),t?`Active`:`Inactive`]})]}),(0,O.jsx)(`div`,{className:`
                        mt-4 grid grid-cols-1 gap-2
                        rounded-lg bg-slate-50 p-3
                        sm:grid-cols-2
                      `,children:e.phone&&(0,O.jsxs)(`div`,{className:`flex items-center gap-2 text-xs text-slate-600`,children:[(0,O.jsx)(h,{className:`h-4 w-4 text-slate-400`}),(0,O.jsx)(`span`,{children:e.phone})]})}),(0,O.jsxs)(`div`,{className:`
                        mt-3 flex items-center justify-between gap-3
                        border-t border-slate-100 pt-3
                      `,children:[(0,O.jsx)(k,{adminId:H(e)}),(0,O.jsxs)(`button`,{type:`button`,onClick:()=>F(e),className:`
                          inline-flex items-center
                          gap-1.5 rounded-lg px-3 py-2
                          text-xs font-semibold
                          text-slate-600 transition-colors
                          hover:bg-red-50
                          hover:text-red-700
                        `,children:[(0,O.jsx)(E,{className:`h-3.5 w-3.5`}),`Change password`]})]})]},H(e)||e.email)})}),(0,O.jsx)(`div`,{className:`hidden overflow-x-auto md:block`,children:(0,O.jsxs)(`table`,{className:`w-full min-w-[860px] text-left`,children:[(0,O.jsx)(`thead`,{children:(0,O.jsxs)(`tr`,{className:`border-b border-slate-200 bg-slate-50`,children:[(0,O.jsx)(`th`,{className:`
                        px-5 py-3 text-xs font-bold
                        uppercase tracking-wide text-slate-500
                      `,children:`Admin`}),(0,O.jsx)(`th`,{className:`
                        px-5 py-3 text-xs font-bold
                        uppercase tracking-wide text-slate-500
                      `,children:`Contact`}),(0,O.jsx)(`th`,{className:`
                        px-5 py-3 text-xs font-bold
                        uppercase tracking-wide text-slate-500
                      `,children:`Password`}),(0,O.jsx)(`th`,{className:`
                        px-5 py-3 text-xs font-bold
                        uppercase tracking-wide text-slate-500
                      `,children:`Status`}),(0,O.jsx)(`th`,{className:`
                        px-5 py-3 text-right text-xs
                        font-bold uppercase tracking-wide
                        text-slate-500
                      `,children:`Actions`})]})}),(0,O.jsx)(`tbody`,{children:g.map((e,t)=>{let n=W(e),r=U(e);return(0,O.jsxs)(`tr`,{className:`
                          border-b border-slate-100
                          transition-colors
                          hover:bg-red-50/40
                          ${t%2==0?`bg-white`:`bg-slate-50/40`}
                        `,children:[(0,O.jsx)(`td`,{className:`px-5 py-4`,children:(0,O.jsxs)(`div`,{className:`flex items-center gap-3`,children:[(0,O.jsx)(`div`,{className:`
                                flex h-9 w-9
                                items-center justify-center
                                rounded-full bg-red-50
                                text-red-700
                              `,children:(0,O.jsx)(d,{className:`h-4 w-4`})}),(0,O.jsxs)(`div`,{className:`min-w-0`,children:[(0,O.jsx)(`p`,{className:`
                                  truncate text-sm
                                  font-semibold text-slate-900
                                `,children:r}),(0,O.jsx)(`p`,{className:`mt-0.5 text-xs text-slate-400`,children:`Administrator`})]})]})}),(0,O.jsx)(`td`,{className:`px-5 py-4`,children:(0,O.jsxs)(`div`,{className:`space-y-1`,children:[(0,O.jsxs)(`div`,{className:`
                                flex items-center gap-2
                                text-sm text-slate-600
                              `,children:[(0,O.jsx)(c,{className:`h-3.5 w-3.5 text-slate-400`}),(0,O.jsx)(`span`,{className:`max-w-[220px] truncate`,children:e.email||`—`})]}),e.phone&&(0,O.jsxs)(`div`,{className:`
                                  flex items-center gap-2
                                  text-xs text-slate-400
                                `,children:[(0,O.jsx)(h,{className:`h-3.5 w-3.5`}),e.phone]})]})}),(0,O.jsx)(`td`,{className:`px-5 py-4`,children:(0,O.jsx)(k,{adminId:H(e)})}),(0,O.jsx)(`td`,{className:`px-5 py-4`,children:(0,O.jsxs)(`button`,{type:`button`,onClick:()=>G(e),disabled:R,className:`
                              inline-flex items-center
                              gap-1.5 rounded-full
                              px-3 py-1 text-xs
                              font-semibold transition-all
                              disabled:cursor-not-allowed
                              disabled:opacity-60
                              ${n?`bg-emerald-50 text-emerald-700 hover:bg-emerald-100`:`bg-slate-100 text-slate-600 hover:bg-slate-200`}
                            `,children:[n?(0,O.jsx)(i,{className:`h-3.5 w-3.5`}):(0,O.jsx)(T,{className:`h-3.5 w-3.5`}),n?`Active`:`Inactive`]})}),(0,O.jsx)(`td`,{className:`px-5 py-4`,children:(0,O.jsx)(`div`,{className:`flex justify-end`,children:(0,O.jsxs)(`button`,{type:`button`,onClick:()=>F(e),className:`
                                inline-flex items-center
                                gap-1.5 rounded-lg
                                px-3 py-2 text-xs
                                font-semibold text-slate-600
                                transition-all
                                hover:bg-red-50
                                hover:text-red-700
                              `,children:[(0,O.jsx)(E,{className:`h-3.5 w-3.5`}),`Change password`]})})})]},H(e)||e.email)})})]})})]}):(0,O.jsxs)(`div`,{className:`
              flex min-h-72 flex-col
              items-center justify-center
              px-4 text-center
            `,children:[(0,O.jsx)(`div`,{className:`
                flex h-14 w-14 items-center
                justify-center rounded-full
                bg-red-50 text-red-700
              `,children:(0,O.jsx)(f,{className:`h-7 w-7`})}),(0,O.jsx)(`h3`,{className:`mt-4 text-sm font-bold text-slate-800`,children:`No Super Admins found`}),(0,O.jsx)(`p`,{className:`mt-1 max-w-sm text-xs leading-5 text-slate-500`,children:`Create your first Super Admin account to start managing administrator access.`}),(0,O.jsxs)(`button`,{type:`button`,onClick:()=>o(!0),className:`
                mt-4 inline-flex items-center gap-2
                rounded-lg bg-red-700 px-4 py-2
                text-xs font-semibold text-white
                transition-colors hover:bg-red-800
              `,children:[(0,O.jsx)(l,{className:`h-4 w-4`}),`Create Super Admin`]})]})}),a&&(0,O.jsx)(`div`,{className:`
      fixed inset-0 z-50 flex items-center justify-center
      bg-slate-950/60 p-3 backdrop-blur-[2px]
      sm:p-5
    `,role:`dialog`,"aria-modal":`true`,"aria-labelledby":`create-super-admin-title`,children:(0,O.jsxs)(`form`,{onSubmit:V,className:`
        flex max-h-[95vh] w-full max-w-2xl
        flex-col overflow-hidden
        rounded-2xl bg-white shadow-2xl
      `,children:[(0,O.jsxs)(`div`,{className:`
          flex shrink-0 items-center justify-between
          border-b border-slate-200
          bg-white px-5 py-5
          sm:px-6 sm:py-6
        `,children:[(0,O.jsxs)(`div`,{className:`flex items-center gap-3`,children:[(0,O.jsx)(`div`,{className:`
              flex h-11 w-11 shrink-0
              items-center justify-center
              rounded-xl bg-red-50
              text-red-700
            `,children:(0,O.jsx)(x,{className:`h-5 w-5`})}),(0,O.jsxs)(`div`,{children:[(0,O.jsx)(`h3`,{id:`create-super-admin-title`,className:`
                text-lg font-bold
                text-slate-900
              `,children:`Create Super Admin`}),(0,O.jsx)(`p`,{className:`mt-1 text-xs text-slate-500 sm:text-sm`,children:`Add a new administrator account`})]})]}),(0,O.jsx)(`button`,{type:`button`,onClick:()=>{M||o(!1)},disabled:M,"aria-label":`Close`,className:`
            flex h-9 w-9 shrink-0
            items-center justify-center
            rounded-lg text-slate-400
            transition-all
            hover:bg-red-50 hover:text-red-700
            disabled:cursor-not-allowed
            disabled:opacity-50
          `,children:(0,O.jsx)(_,{className:`h-5 w-5`})})]}),(0,O.jsx)(`div`,{className:`flex-1 overflow-y-auto`,children:(0,O.jsxs)(`div`,{className:`
            grid grid-cols-1 gap-6
            p-5
            sm:grid-cols-2
            sm:p-7
          `,children:[(0,O.jsx)(r,{label:`First name`,required:!0,children:(0,O.jsx)(`input`,{name:`firstName`,type:`text`,required:!0,autoComplete:`given-name`,placeholder:`Enter first name`,className:`
                h-8 w-full rounded-[3px] border border-slate-300 bg-white px-2 text-[13px] text-slate-800 outline-none transition-colors hover:border-slate-400 focus:border-sky-500 focus:ring-1 focus:ring-sky-200
                h-12 w-full
                rounded-lg
                px-4
                text-sm
                transition-all
                focus:border-red-600
                focus:outline-none
                focus:ring-2
                focus:ring-red-100
              `})}),(0,O.jsx)(r,{label:`Last name`,children:(0,O.jsx)(`input`,{name:`lastName`,type:`text`,autoComplete:`family-name`,placeholder:`Enter last name`,className:`
                h-8 w-full rounded-[3px] border border-slate-300 bg-white px-2 text-[13px] text-slate-800 outline-none transition-colors hover:border-slate-400 focus:border-sky-500 focus:ring-1 focus:ring-sky-200
                h-12 w-full
                rounded-lg
                px-4
                text-sm
                transition-all
                focus:border-red-600
                focus:outline-none
                focus:ring-2
                focus:ring-red-100
              `})}),(0,O.jsx)(r,{label:`Email address`,required:!0,children:(0,O.jsxs)(`div`,{className:`relative`,children:[(0,O.jsx)(c,{className:`
                  pointer-events-none
                  absolute left-3.5 top-1/2
                  h-4 w-4
                  -translate-y-1/2
                  text-slate-400
                `}),(0,O.jsx)(`input`,{name:`email`,type:`email`,required:!0,autoComplete:`email`,placeholder:`admin@example.com`,className:`
                  h-8 w-full rounded-[3px] border border-slate-300 bg-white px-2 text-[13px] text-slate-800 outline-none transition-colors hover:border-slate-400 focus:border-sky-500 focus:ring-1 focus:ring-sky-200
                  h-12 w-full
                  rounded-lg
                  pl-11 pr-4
                  text-sm
                  transition-all
                  focus:border-red-600
                  focus:outline-none
                  focus:ring-2
                  focus:ring-red-100
                `})]})}),(0,O.jsx)(r,{label:`Phone number`,children:(0,O.jsxs)(`div`,{className:`relative`,children:[(0,O.jsx)(h,{className:`
                  pointer-events-none
                  absolute left-3.5 top-1/2
                  h-4 w-4
                  -translate-y-1/2
                  text-slate-400
                `}),(0,O.jsx)(`input`,{name:`phone`,type:`tel`,autoComplete:`tel`,placeholder:`Enter phone number`,className:`
                  h-8 w-full rounded-[3px] border border-slate-300 bg-white px-2 text-[13px] text-slate-800 outline-none transition-colors hover:border-slate-400 focus:border-sky-500 focus:ring-1 focus:ring-sky-200
                  h-12 w-full
                  rounded-lg
                  pl-11 pr-4
                  text-sm
                  transition-all
                  focus:border-red-600
                  focus:outline-none
                  focus:ring-2
                  focus:ring-red-100
                `})]})}),(0,O.jsx)(`div`,{className:`sm:col-span-2`,children:(0,O.jsxs)(r,{label:`Password`,required:!0,children:[(0,O.jsxs)(`div`,{className:`relative`,children:[(0,O.jsx)(E,{className:`
                    pointer-events-none
                    absolute left-3.5 top-1/2
                    h-4 w-4
                    -translate-y-1/2
                    text-slate-400
                  `}),(0,O.jsx)(`input`,{name:`password`,type:`password`,required:!0,minLength:6,autoComplete:`new-password`,placeholder:`Enter a secure password`,className:`
                    h-8 w-full rounded-[3px] border border-slate-300 bg-white px-2 text-[13px] text-slate-800 outline-none transition-colors hover:border-slate-400 focus:border-sky-500 focus:ring-1 focus:ring-sky-200
                    h-12 w-full
                    rounded-lg
                    pl-11 pr-4
                    text-sm
                    transition-all
                    focus:border-red-600
                    focus:outline-none
                    focus:ring-2
                    focus:ring-red-100
                  `})]}),(0,O.jsx)(`p`,{className:`mt-2 text-xs text-slate-400`,children:`Password must be at least 6 characters.`})]})})]})}),(0,O.jsxs)(`div`,{className:`
          flex shrink-0
          flex-col-reverse gap-3
          border-t border-slate-200
          bg-slate-50
          px-5 py-4
          sm:flex-row sm:items-center
          sm:justify-end
          sm:px-7 sm:py-5
        `,children:[(0,O.jsx)(`button`,{type:`button`,onClick:()=>o(!1),disabled:M,className:`
            h-11 w-full
            rounded-lg
            border border-slate-200
            bg-white px-6
            text-sm font-semibold
            text-slate-600
            transition-all
            hover:border-slate-300
            hover:bg-slate-100
            disabled:cursor-not-allowed
            disabled:opacity-50
            sm:w-auto
          `,children:`Cancel`}),(0,O.jsxs)(`button`,{type:`submit`,disabled:M,className:`
            inline-flex h-11 w-full
            items-center justify-center
            gap-2 rounded-lg
            bg-red-700 px-7
            text-sm font-semibold
            text-white
            shadow-sm
            transition-all
            hover:bg-red-800
            hover:shadow-md
            active:scale-[0.98]
            disabled:cursor-not-allowed
            disabled:opacity-60
            sm:w-auto
          `,children:[M&&(0,O.jsx)(s,{className:`h-4 w-4 animate-spin`}),M?`Creating...`:`Create Super Admin`]})]})]})}),I&&(0,O.jsx)(`div`,{className:`
            fixed inset-0 z-[60]
            flex items-center justify-center
            bg-slate-950/60 p-4
            backdrop-blur-[2px]
          `,role:`dialog`,"aria-modal":`true`,"aria-labelledby":`status-confirm-title`,children:(0,O.jsxs)(`div`,{className:`
              w-full max-w-md overflow-hidden
              rounded-2xl bg-white shadow-2xl
            `,children:[(0,O.jsxs)(`div`,{className:`p-5 sm:p-6`,children:[(0,O.jsxs)(`div`,{className:`flex items-start gap-4`,children:[(0,O.jsx)(`div`,{className:`
                    flex h-11 w-11 shrink-0
                    items-center justify-center
                    rounded-full bg-red-50
                    text-red-700
                  `,children:(0,O.jsx)(u,{className:`h-5 w-5`})}),(0,O.jsxs)(`div`,{className:`min-w-0`,children:[(0,O.jsx)(`h3`,{id:`status-confirm-title`,className:`
                      text-base font-bold
                      text-slate-900
                    `,children:W(I)?`Deactivate Super Admin?`:`Activate Super Admin?`}),(0,O.jsxs)(`p`,{className:`mt-1 text-sm leading-5 text-slate-500`,children:[`Are you sure you want to`,` `,(0,O.jsx)(`span`,{className:`font-semibold text-slate-700`,children:W(I)?`deactivate`:`activate`}),` `,`this Super Admin account?`]})]})]}),(0,O.jsx)(`div`,{className:`
                  mt-5 rounded-xl
                  border border-slate-200
                  bg-slate-50 p-3
                `,children:(0,O.jsxs)(`div`,{className:`flex items-center gap-3`,children:[(0,O.jsx)(`div`,{className:`
                      flex h-10 w-10 shrink-0
                      items-center justify-center
                      rounded-full bg-red-50
                      text-red-700
                    `,children:(0,O.jsx)(d,{className:`h-5 w-5`})}),(0,O.jsxs)(`div`,{className:`min-w-0`,children:[(0,O.jsx)(`p`,{className:`truncate text-sm font-semibold text-slate-900`,children:U(I)}),(0,O.jsx)(`p`,{className:`truncate text-xs text-slate-500`,children:I.email||`—`})]})]})})]}),(0,O.jsxs)(`div`,{className:`
                flex flex-col-reverse gap-2
                border-t border-slate-200
                bg-slate-50 px-5 py-4
                sm:flex-row sm:justify-end
              `,children:[(0,O.jsx)(`button`,{type:`button`,onClick:()=>{R||L(null)},disabled:R,className:`
                  h-10 rounded-lg
                  border border-slate-200
                  bg-white px-5
                  text-sm font-semibold
                  text-slate-600
                  transition-colors
                  hover:bg-slate-100
                  disabled:cursor-not-allowed
                  disabled:opacity-50
                `,children:`Cancel`}),(0,O.jsxs)(`button`,{type:`button`,onClick:async()=>{if(!I)return;let e=H(I);if(!e){m.error(`Super Admin ID is missing.`);return}let t=!W(I);z(!0);try{await n({id:e,payload:{isActive:t}}),m.success(t?`Super Admin activated successfully.`:`Super Admin deactivated successfully.`),L(null),await B()}catch(e){m.error(e?.message||`Could not update Super Admin status.`)}finally{z(!1)}},disabled:R,className:`
                  inline-flex h-10
                  items-center justify-center
                  gap-2 rounded-lg
                  bg-red-700 px-5
                  text-sm font-semibold
                  text-white shadow-sm
                  transition-all
                  hover:bg-red-800
                  disabled:cursor-not-allowed
                  disabled:opacity-60
                `,children:[R&&(0,O.jsx)(s,{className:`h-4 w-4 animate-spin`}),R?`Updating...`:W(I)?`Deactivate`:`Activate`]})]})]})}),P&&(0,O.jsx)(b,{onClose:()=>F(null),defaultEmail:P.email,superAdminId:P.id||P._id})]})}export{A as SuperAdminsManagement};