import{o as e,t}from"./jsx-runtime-DVpe7PXp.js";import{t as n}from"./react-Dps3yNUO.js";import{D as r,O as i,S as a,_ as o,d as ee,g as te,j as ne,n as re,p as ie,v as ae,w as oe,x as se,y as ce}from"./directory-shared-CtjPvLUJ.js";import{t as le}from"./eye-DGZnmR0v.js";import{t as s}from"./handshake-DlqGKCQu.js";import{t as ue}from"./link-2-Cya99Idt.js";import{t as de}from"./loader-circle-DT9DtKTa.js";import{t as fe}from"./refresh-cw-B-OOosDM.js";import{t as pe}from"./search-dJz1lfKw.js";import{t as me}from"./user-XtNnq-Zt.js";import{t as he}from"./users-CBcTV40S.js";import{c,l,v as ge}from"./index-CXECB6AG.js";import{t as _e}from"./config-CrWrgA7M.js";import{t as ve}from"./AppLink-BI3e7IAu.js";import{c as ye,f as be,l as xe}from"./membersApi-CrlDghiW.js";import{f as Se,l as Ce,s as we}from"./partnersApi-CNH2NK81.js";import{n as u,t as Te}from"./useApiCall-B0EBj9IB.js";import{n as Ee,r as De,t as Oe}from"./RenewPeriodFields-ty9t_rJi.js";var ke=ge(`tag`,[[`path`,{d:`M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z`,key:`vktsd0`}],[`circle`,{cx:`7.5`,cy:`7.5`,r:`.5`,fill:`currentColor`,key:`kqv944`}]]),d=e(n(),1),f=t(),p=10,Ae=400,je=[{value:`members`,label:`Members`,icon:he},{value:`partners`,label:`Partners`,icon:s}];function Me(e,t){return e===`members`?t.memberId:t.partnerId}function m(e,t){return e===`members`?t.memberName:t.partnerName}function h(e){return e===`members`?`/admin/directory/$slug/details`:`/admin/directory/partner/$slug/details`}function g(e,t){return e===`members`?te(t):o(t)}function _(e){return e?.photo?_e(e.photo):``}function v({title:e,description:t,children:n,onClose:r,maxWidth:i=`max-w-md`}){return(0,f.jsx)(`div`,{className:`fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-[2px]`,role:`dialog`,"aria-modal":`true`,onClick:e=>{e.target===e.currentTarget&&r()},onKeyDown:e=>{e.key===`Escape`&&r()},tabIndex:-1,children:(0,f.jsxs)(`div`,{className:`w-full ${i} max-h-[90vh] overflow-y-auto overflow-hidden rounded-md border border-slate-200 bg-white shadow-2xl`,children:[(0,f.jsxs)(`div`,{className:`flex items-start justify-between gap-3 border-b border-slate-200 bg-slate-50 px-4 py-3 sm:px-5`,children:[(0,f.jsxs)(`div`,{className:`min-w-0`,children:[(0,f.jsx)(`h3`,{className:`text-sm font-bold text-slate-800 sm:text-base`,children:e}),t&&(0,f.jsx)(`p`,{className:`mt-1 text-xs leading-5 text-slate-500 sm:text-[13px]`,children:t})]}),(0,f.jsx)(`button`,{type:`button`,onClick:r,"aria-label":`Close`,className:`shrink-0 rounded-md p-1.5 text-slate-400 transition hover:bg-slate-200 hover:text-slate-700`,children:(0,f.jsx)(l,{className:`h-4 w-4`})})]}),(0,f.jsx)(`div`,{className:`p-4 sm:p-5`,children:n})]})})}function Ne(){return(0,f.jsx)(`div`,{className:`overflow-hidden rounded-md border border-slate-200 bg-white`,children:(0,f.jsx)(`div`,{className:`w-full overflow-x-auto`,children:(0,f.jsxs)(`table`,{className:`w-full min-w-[950px] text-left text-[13px]`,children:[(0,f.jsx)(`thead`,{className:`bg-slate-50`,children:(0,f.jsx)(`tr`,{children:[`ID`,`Name`,`Designation`,`Mobile`,`Status`,`Days Remaining`,`Actions`].map(e=>(0,f.jsx)(`th`,{className:`whitespace-nowrap border-b border-slate-200 px-3 py-3 font-semibold text-slate-500`,children:e},e))})}),(0,f.jsx)(`tbody`,{children:Array.from({length:7}).map((e,t)=>(0,f.jsxs)(`tr`,{className:`border-b border-slate-100 last:border-b-0`,children:[(0,f.jsx)(`td`,{className:`px-3 py-3`,children:(0,f.jsx)(`div`,{className:`h-4 w-20 animate-pulse rounded bg-slate-200`})}),(0,f.jsx)(`td`,{className:`px-3 py-3`,children:(0,f.jsx)(`div`,{className:`h-4 w-28 animate-pulse rounded bg-slate-200`})}),(0,f.jsx)(`td`,{className:`px-3 py-3`,children:(0,f.jsx)(`div`,{className:`h-4 w-28 animate-pulse rounded bg-slate-200`})}),(0,f.jsx)(`td`,{className:`px-3 py-3`,children:(0,f.jsx)(`div`,{className:`h-4 w-24 animate-pulse rounded bg-slate-200`})}),(0,f.jsx)(`td`,{className:`px-3 py-3`,children:(0,f.jsx)(`div`,{className:`h-6 w-16 animate-pulse rounded-full bg-slate-200`})}),(0,f.jsx)(`td`,{className:`px-3 py-3`,children:(0,f.jsx)(`div`,{className:`h-4 w-24 animate-pulse rounded bg-slate-200`})}),(0,f.jsx)(`td`,{className:`px-3 py-3`,children:(0,f.jsx)(`div`,{className:`ml-auto h-4 w-40 animate-pulse rounded bg-slate-200`})})]},t))})]})})})}function y(){let e=Te(ye),t=Te(we),n=u(xe),o=u(Ce),te=u(be),s=u(Se),[l,ge]=(0,d.useState)(`members`),[_e,y]=(0,d.useState)([]),[b,Pe]=(0,d.useState)({page:1,total:0,totalPages:1}),x=(0,d.useRef)(0),[S,C]=(0,d.useState)(`all`),[Fe,w]=(0,d.useState)(!0),[T,E]=(0,d.useState)(``),[D,O]=(0,d.useState)(``),[k,A]=(0,d.useState)(``),[j,M]=(0,d.useState)(1),[N,Ie]=(0,d.useState)(null),[P,F]=(0,d.useState)(``),[I,L]=(0,d.useState)(``),[Le,R]=(0,d.useState)(``),[z,Re]=(0,d.useState)(``),[B,ze]=(0,d.useState)(!1),[Be,V]=(0,d.useState)(``),[H,Ve]=(0,d.useState)(null),[U,W]=(0,d.useState)(``),[G,K]=(0,d.useState)(!1),[He,q]=(0,d.useState)(``);(0,d.useEffect)(()=>{let e=setTimeout(()=>{A(D.trim())},Ae);return()=>clearTimeout(e)},[D]),(0,d.useEffect)(()=>{M(1)},[l,k,S]);let J=async()=>{let n=++x.current,r=()=>n!==x.current;w(!0),E(``);try{let n={search:k,status:S,page:j,limit:p},i=l===`members`?await e(n):await t(n);if(r())return;let a=(l===`members`?i?.members:i?.partners)||[],o=i?.pagination||{page:1,total:a.length,totalPages:1};if(a.length===0&&j>1&&o.total>0){M(Math.max(1,o.totalPages));return}y(a),Pe(o)}catch(e){if(r())return;let t=e?.message||`Could not load ${l}.`;E(t),c.error(t)}finally{r()||w(!1)}};(0,d.useEffect)(()=>{J()},[l,k,S,j]);let Y=b.totalPages,X=b.page,Z=_e,Ue=e=>{ge(e),O(``),A(``),C(`all`),M(1)},We=e=>{Ie(e),F(e?.validityTo?String(e.validityTo).slice(0,10):``),L(``),R(``),Re(De(e).from),V(``)},Q=()=>{B||(Ie(null),F(``),L(``),R(``),V(``))},Ge=async e=>{if(e.preventDefault(),!P){V(`Choose the new validity date.`);return}if(ne(P)){V(`Validity date must be after today.`);return}if(!z){V(`Choose the Valid From date.`);return}if(I&&Number(I)<0){V(`Amount cannot be negative.`);return}ze(!0),V(``);try{let e={validityFrom:z,validityTo:P,amount:I?Number(I):void 0,note:Le.trim()||void 0};l===`members`?await n({id:N.id,...e}):await o({id:N.id,...e}),await J(),c.success(`${m(l,N)||`Record`} renewed successfully through ${P}${I?` for ₹${I}`:``}.`),Q()}catch(e){let t=e?.message||`Could not renew.`;V(t),c.error(t)}finally{ze(!1)}},Ke=async e=>{let t=l===`members`?ae(e):ce(e);try{await se(t),c.success(`Public profile link copied — paste it in any browser to view.`)}catch{c.error(`Could not copy the link.`)}},qe=e=>{Ve(e),W(e?.designation||``),q(``)},$=()=>{G||(Ve(null),W(``),q(``))};return(0,f.jsxs)(`section`,{className:`w-full space-y-3`,children:[(0,f.jsx)(`div`,{className:`rounded-md border border-slate-200 bg-white shadow-sm`,children:(0,f.jsxs)(`div`,{className:`flex items-center gap-3 px-4 py-3 sm:px-5`,children:[(0,f.jsx)(`div`,{className:`flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-red-50`,children:(0,f.jsx)(fe,{className:`h-4 w-4 text-red-700`})}),(0,f.jsxs)(`div`,{className:`min-w-0`,children:[(0,f.jsx)(`h2`,{className:`text-base font-bold text-slate-800 sm:text-lg`,children:`Reset Directory`}),(0,f.jsx)(`p`,{className:`text-xs text-slate-500 sm:text-[13px]`,children:`Renew member or partner validity and update designations.`})]})]})}),(0,f.jsxs)(`div`,{className:`overflow-hidden rounded-md border border-slate-200 bg-white shadow-sm`,children:[(0,f.jsx)(`div`,{className:`border-b border-slate-200 bg-slate-50/70 px-3 py-3 sm:px-4`,children:(0,f.jsxs)(`div`,{className:`flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between`,children:[(0,f.jsx)(`div`,{className:`flex w-full rounded-md border border-slate-200 bg-white p-1 sm:w-auto`,role:`tablist`,children:je.map(e=>{let t=e.icon,n=l===e.value;return(0,f.jsxs)(`button`,{type:`button`,role:`tab`,"aria-selected":n,onClick:()=>Ue(e.value),className:`
                      inline-flex h-9 flex-1 items-center
                      justify-center gap-1.5 rounded-[4px]
                      px-4 text-xs font-semibold
                      transition-all
                      sm:flex-none sm:text-[13px]

                      ${n?`bg-red-700 text-white shadow-sm`:`text-slate-600 hover:bg-slate-100`}
                    `,children:[(0,f.jsx)(t,{className:`h-3.5 w-3.5`}),e.label]},e.value)})}),(0,f.jsx)(`select`,{value:S,onChange:e=>C(e.target.value),"aria-label":`Filter ${l} by status`,className:`
                h-9 w-full rounded-md border border-slate-300
                bg-white px-3 text-xs outline-none
                transition-all hover:border-slate-400
                focus:border-red-700 focus:ring-2 focus:ring-red-100
                sm:text-[13px] lg:w-auto
              `,children:ee.map(e=>(0,f.jsx)(`option`,{value:e.value,children:e.label},e.value))}),(0,f.jsx)(`div`,{className:`w-full lg:max-w-sm`,children:(0,f.jsxs)(`label`,{className:`relative block`,children:[(0,f.jsx)(pe,{className:`pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400`}),(0,f.jsx)(`input`,{value:D,onChange:e=>O(e.target.value),placeholder:l===`members`?`Search Member ID, name or mobile...`:`Search Partner ID, name or mobile...`,"aria-label":`Search ${l}`,className:`
                    h-9 w-full rounded-md
                    border border-slate-300
                    bg-white pl-9 pr-3
                    text-xs outline-none
                    transition-all
                    placeholder:text-slate-400
                    hover:border-slate-400
                    focus:border-red-700
                    focus:ring-2
                    focus:ring-red-100
                    sm:text-[13px]
                  `})]})})]})}),T&&(0,f.jsx)(`div`,{className:`mx-3 mt-3 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 sm:mx-4 sm:text-[13px]`,children:T}),Fe?(0,f.jsx)(`div`,{className:`p-3 sm:p-4`,children:(0,f.jsx)(Ne,{})}):Z.length===0?(0,f.jsxs)(`div`,{className:`mx-3 my-4 flex min-h-52 flex-col items-center justify-center rounded-md border border-dashed border-slate-300 bg-slate-50/50 sm:mx-4`,children:[(0,f.jsx)(`div`,{className:`flex h-12 w-12 items-center justify-center rounded-full bg-slate-100`,children:(0,f.jsx)(he,{className:`h-6 w-6 text-slate-400`})}),(0,f.jsx)(`p`,{className:`mt-3 text-sm font-semibold text-slate-600`,children:k?`No ${l} match your search`:`No ${l} found`}),k&&(0,f.jsx)(`p`,{className:`mt-1 text-xs text-slate-400`,children:`Try searching with a different keyword.`})]}):(0,f.jsxs)(`div`,{className:`p-3 sm:p-4`,children:[(0,f.jsxs)(`div`,{className:`overflow-hidden rounded-md border border-slate-200`,children:[(0,f.jsx)(`div`,{className:`w-full overflow-x-auto scrollbar-hide`,children:(0,f.jsxs)(`table`,{className:`w-full min-w-[1050px] text-left text-[13px]`,children:[(0,f.jsx)(`thead`,{className:`bg-slate-50`,children:(0,f.jsxs)(`tr`,{children:[(0,f.jsx)(`th`,{className:`whitespace-nowrap border-b border-slate-200 px-3 py-3 font-semibold text-slate-500`,children:l===`members`?`Member ID`:`Partner ID`}),(0,f.jsx)(`th`,{className:`whitespace-nowrap border-b border-slate-200 px-3 py-3 font-semibold text-slate-500`,children:`Name`}),(0,f.jsx)(`th`,{className:`whitespace-nowrap border-b border-slate-200 px-3 py-3 font-semibold text-slate-500`,children:`Designation`}),(0,f.jsx)(`th`,{className:`whitespace-nowrap border-b border-slate-200 px-3 py-3 font-semibold text-slate-500`,children:`Mobile`}),(0,f.jsx)(`th`,{className:`whitespace-nowrap border-b border-slate-200 px-3 py-3 font-semibold text-slate-500`,children:`Status`}),(0,f.jsx)(`th`,{className:`whitespace-nowrap border-b border-slate-200 px-3 py-3 font-semibold text-slate-500`,children:`Days Remaining`}),(0,f.jsx)(`th`,{className:`whitespace-nowrap border-b border-slate-200 px-3 py-3 text-center font-semibold text-slate-500`,children:`Actions`})]})}),(0,f.jsx)(`tbody`,{children:Z.map((e,t)=>{let n=e.isActive&&!r(e),o=r(e),ee=i(e);return(0,f.jsxs)(`tr`,{className:`
                            border-b border-slate-100
                            last:border-b-0
                            transition-colors
                            hover:bg-red-50/40
                            ${t%2==0?`bg-white`:`bg-slate-50/40`}
                          `,children:[(0,f.jsx)(`td`,{className:`whitespace-nowrap px-3 py-3`,children:(0,f.jsx)(`span`,{className:`font-semibold text-slate-700`,children:Me(l,e)||`—`})}),(0,f.jsx)(`td`,{className:`px-3 py-3`,children:(0,f.jsxs)(ve,{to:h(l),params:{slug:g(l,e)},className:`group flex items-center gap-2.5`,children:[(0,f.jsxs)(`div`,{className:`h-9 w-9 shrink-0 overflow-hidden rounded-full border border-slate-200 bg-slate-100`,children:[_(e)?(0,f.jsx)(`img`,{src:_(e),alt:m(l,e)||`Profile`,className:`h-full w-full object-cover`,onError:e=>{e.currentTarget.style.display=`none`,e.currentTarget.nextElementSibling?.classList.remove(`hidden`)}}):null,(0,f.jsx)(`div`,{className:`h-full w-full items-center justify-center ${_(e)?`hidden`:`flex`}`,children:(0,f.jsx)(me,{className:`h-4 w-4 text-slate-400`})})]}),(0,f.jsx)(`span`,{className:`min-w-0 truncate font-semibold text-slate-700 transition-colors group-hover:text-red-700 group-hover:underline`,children:m(l,e)||`—`})]})}),(0,f.jsx)(`td`,{className:`max-w-[180px] px-3 py-3`,children:(0,f.jsx)(`span`,{className:`block truncate text-slate-600`,children:e.designation||`—`})}),(0,f.jsx)(`td`,{className:`whitespace-nowrap px-3 py-3 text-slate-600`,children:e.mobile||`—`}),(0,f.jsx)(`td`,{className:`whitespace-nowrap px-3 py-3`,children:(0,f.jsx)(ie,{active:n})}),(0,f.jsx)(`td`,{className:`
                              whitespace-nowrap
                              px-3 py-3
                              font-semibold

                              ${o?`text-red-600`:ee?`text-amber-600`:`text-slate-600`}
                            `,children:oe(a(e))}),(0,f.jsx)(`td`,{className:`whitespace-nowrap px-3 py-3`,children:(0,f.jsxs)(`div`,{className:`flex justify-center gap-1`,children:[(0,f.jsxs)(ve,{to:h(l),params:{slug:g(l,e)},className:`
                                  inline-flex h-8
                                  items-center gap-1
                                  rounded-[4px]
                                  px-2.5
                                  text-xs font-semibold
                                  text-slate-600
                                  transition-colors
                                  hover:bg-red-50
                                  hover:text-red-700
                                `,children:[(0,f.jsx)(le,{className:`h-3.5 w-3.5`}),`View`]}),(0,f.jsxs)(`button`,{type:`button`,onClick:()=>We(e),className:`
                                  inline-flex h-8
                                  items-center gap-1
                                  rounded-[4px]
                                  px-2.5
                                  text-xs font-semibold
                                  text-emerald-700
                                  transition-colors
                                  hover:bg-emerald-50
                                  hover:text-emerald-800
                                `,children:[(0,f.jsx)(fe,{className:`h-3.5 w-3.5`}),`Renew`]}),(0,f.jsxs)(`button`,{type:`button`,onClick:()=>qe(e),className:`
                                  inline-flex h-8
                                  items-center gap-1
                                  rounded-[4px]
                                  px-2.5
                                  text-xs font-semibold
                                  text-slate-600
                                  transition-colors
                                  hover:bg-red-50
                                  hover:text-red-700
                                `,children:[(0,f.jsx)(ke,{className:`h-3.5 w-3.5`}),`Designation`]}),(0,f.jsxs)(`button`,{type:`button`,onClick:()=>Ke(e),title:`Copy public profile link`,className:`
                                  inline-flex h-8
                                  items-center gap-1
                                  rounded-[4px]
                                  px-2.5
                                  text-xs font-semibold
                                  text-sky-700
                                  transition-colors
                                  hover:bg-sky-50
                                `,children:[(0,f.jsx)(ue,{className:`h-3.5 w-3.5`}),`Copy Link`]})]})})]},e.id)})})]})}),(0,f.jsx)(`div`,{className:`border-t border-slate-100 bg-slate-50 px-3 py-1.5 text-center text-[11px] text-slate-400 sm:hidden`,children:`Swipe left/right to view the complete table`})]}),b.total>0&&(0,f.jsxs)(`div`,{className:`mt-3 flex flex-col gap-2 text-xs text-slate-500 sm:flex-row sm:items-center sm:justify-between sm:text-[13px]`,children:[(0,f.jsxs)(`p`,{children:[`Showing`,` `,(0,f.jsx)(`span`,{className:`font-semibold text-slate-700`,children:(X-1)*p+1}),` `,`–`,` `,(0,f.jsx)(`span`,{className:`font-semibold text-slate-700`,children:Math.min(X*p,b.total)}),` `,`of `,(0,f.jsx)(`span`,{className:`font-semibold text-slate-700`,children:b.total})]}),(0,f.jsxs)(`div`,{className:`flex items-center gap-2`,children:[(0,f.jsx)(`button`,{type:`button`,disabled:X<=1,onClick:()=>M(e=>Math.max(1,e-1)),className:`
                      h-8 rounded-md
                      border border-slate-300
                      bg-white px-3
                      text-xs font-semibold
                      text-slate-600
                      transition-colors
                      hover:border-red-700
                      hover:text-red-700
                      disabled:cursor-not-allowed
                      disabled:opacity-40
                    `,children:`Previous`}),(0,f.jsxs)(`span`,{className:`
                      hidden h-8
                      items-center
                      rounded-md
                      border border-slate-200
                      bg-slate-50
                      px-3
                      text-xs font-semibold
                      text-slate-600
                      sm:flex
                    `,children:[`Page `,X,` of `,Y]}),(0,f.jsx)(`button`,{type:`button`,disabled:X>=Y,onClick:()=>M(e=>Math.min(Y,e+1)),className:`
                      h-8 rounded-md
                      border border-slate-300
                      bg-white px-3
                      text-xs font-semibold
                      text-slate-600
                      transition-colors
                      hover:border-red-700
                      hover:text-red-700
                      disabled:cursor-not-allowed
                      disabled:opacity-40
                    `,children:`Next`})]})]})]})]}),N&&(0,f.jsx)(v,{title:`Renew ${l===`members`?`Membership`:`Partnership`}`,description:`${m(l,N)||`This record`} — set the new validity details.`,onClose:Q,children:(0,f.jsxs)(`form`,{onSubmit:Ge,className:`space-y-4`,children:[(0,f.jsx)(Ee,{record:N,value:z,onChange:Re,disabled:B}),(0,f.jsxs)(`label`,{className:`block text-[13px] font-semibold text-slate-700`,children:[`New Validity To`,(0,f.jsx)(`input`,{type:`date`,required:!0,value:P,onChange:e=>F(e.target.value),className:`h-8 w-full rounded-[3px] border border-slate-300 bg-white px-2 text-[13px] text-slate-800 outline-none transition-colors hover:border-slate-400 focus:border-sky-500 focus:ring-1 focus:ring-sky-200 mt-1 w-full`})]}),(0,f.jsxs)(`label`,{className:`block text-[13px] font-semibold text-slate-700`,children:[`Amount Paid (₹)`,(0,f.jsx)(`input`,{type:`number`,min:`0`,step:`0.01`,placeholder:`e.g. 5000`,value:I,onChange:e=>L(e.target.value),className:`h-8 w-full rounded-[3px] border border-slate-300 bg-white px-2 text-[13px] text-slate-800 outline-none transition-colors hover:border-slate-400 focus:border-sky-500 focus:ring-1 focus:ring-sky-200 mt-1 w-full`})]}),(0,f.jsx)(Oe,{value:Le,onChange:R,disabled:B}),Be&&(0,f.jsx)(`div`,{role:`alert`,className:`rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 sm:text-[13px]`,children:Be}),(0,f.jsxs)(`div`,{className:`flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end`,children:[(0,f.jsx)(`button`,{type:`button`,onClick:Q,disabled:B,className:`
                  h-9 rounded-md
                  bg-slate-100 px-4
                  text-xs font-semibold
                  text-slate-600
                  transition-colors
                  hover:bg-slate-200
                  disabled:opacity-50
                  sm:text-[13px]
                `,children:`Cancel`}),(0,f.jsxs)(`button`,{type:`submit`,disabled:B,className:`
                  inline-flex h-9
                  items-center justify-center
                  gap-2 rounded-md
                  bg-red-700 px-4
                  text-xs font-semibold
                  text-white
                  shadow-sm
                  transition-colors
                  hover:bg-red-800
                  disabled:cursor-not-allowed
                  disabled:opacity-60
                  sm:text-[13px]
                `,children:[B&&(0,f.jsx)(de,{className:`h-4 w-4 animate-spin`}),B?`Renewing...`:`Renew`]})]})]})}),H&&(0,f.jsx)(v,{title:`Change Designation`,description:`${m(l,H)||`This record`} — update their designation.`,onClose:$,children:(0,f.jsxs)(`form`,{onSubmit:async e=>{if(e.preventDefault(),!U.trim()){q(`Choose or enter a designation.`);return}K(!0),q(``);try{let e=U.trim();l===`members`?await te({id:H.id,member:{memberId:H.memberId,memberName:H.memberName,designation:e}}):await s({id:H.id,partner:{designation:e}}),await J(),c.success(`${m(l,H)||`Record`}'s designation updated to ${U.trim()}.`),$()}catch(e){let t=e?.message||`Could not update designation.`;q(t),c.error(t)}finally{K(!1)}},className:`space-y-4`,children:[(0,f.jsxs)(`label`,{className:`block text-[13px] font-semibold text-slate-700`,children:[`Designation`,(0,f.jsx)(`div`,{className:`mt-1`,children:(0,f.jsx)(re,{value:U,onChange:W})})]}),He&&(0,f.jsx)(`div`,{role:`alert`,className:`rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700 sm:text-[13px]`,children:He}),(0,f.jsxs)(`div`,{className:`flex flex-col-reverse gap-2 border-t border-slate-200 pt-4 sm:flex-row sm:justify-end`,children:[(0,f.jsx)(`button`,{type:`button`,onClick:$,disabled:G,className:`
                  h-9 rounded-md
                  bg-slate-100 px-4
                  text-xs font-semibold
                  text-slate-600
                  transition-colors
                  hover:bg-slate-200
                  disabled:opacity-50
                  sm:text-[13px]
                `,children:`Cancel`}),(0,f.jsxs)(`button`,{type:`submit`,disabled:G,className:`
                  inline-flex h-9
                  items-center justify-center
                  gap-2 rounded-md
                  bg-red-700 px-4
                  text-xs font-semibold
                  text-white
                  shadow-sm
                  transition-colors
                  hover:bg-red-800
                  disabled:cursor-not-allowed
                  disabled:opacity-60
                  sm:text-[13px]
                `,children:[G&&(0,f.jsx)(de,{className:`h-4 w-4 animate-spin`}),G?`Saving...`:`Save Designation`]})]})]})})]})}export{y as ResetDirectory};