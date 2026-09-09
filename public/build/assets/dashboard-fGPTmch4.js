m();g();b();h();function m(){const t=document.querySelector("[data-sidebar-toggle]"),s=document.querySelector("[data-sidebar]"),a=document.querySelector("[data-sidebar-backdrop]");if(!t||!s)return;const i=o=>{s.classList.toggle("-translate-x-full",!o),a==null||a.classList.toggle("hidden",!o),t.setAttribute("aria-expanded",o?"true":"false")};t.addEventListener("click",()=>{i(s.classList.contains("-translate-x-full"))}),a==null||a.addEventListener("click",()=>i(!1)),document.addEventListener("keydown",o=>{o.key==="Escape"&&i(!1)})}function g(){const t=document.querySelector("[data-notification-bell]");if(!t)return;const s=t.querySelector("[data-bell-toggle]"),a=t.querySelector("[data-bell-panel]"),i=t.querySelector("[data-bell-list]"),o=t.querySelector("[data-bell-badge]"),d=document.querySelector("[data-notification-toasts]"),l=t.dataset.notificationBell,u=new Set((t.dataset.seen??"").split(",").map(n=>n.trim()).filter(Boolean));s==null||s.addEventListener("click",()=>{a.classList.toggle("hidden")}),document.addEventListener("click",n=>{t.contains(n.target)||a==null||a.classList.add("hidden")});const p=n=>{if(i){if(!n.length){i.innerHTML='<p class="px-4 py-6 text-center text-sm text-ink-400">Tidak ada notifikasi baru.</p>';return}i.innerHTML=n.map(e=>`
                    <a href="${x(e.url??"#")}"
                       class="block border-b border-ink-100 px-4 py-3 transition-colors last:border-0 hover:bg-brand-50/50">
                        <p class="text-sm font-bold text-ink-900">${c(e.title)}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-ink-500">${c(e.message)}</p>
                        <p class="mt-1 text-[0.65rem] text-ink-400">${c(e.created_at??"")}</p>
                    </a>
                `).join("")}},f=n=>{var r;if(!d)return;const e=document.createElement("div");e.className="pointer-events-auto w-80 max-w-[calc(100vw-2rem)] rounded-2xl border border-ink-100 bg-white p-4 shadow-card-hover",e.innerHTML=`
            <div class="flex items-start gap-3">
                <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-brand-600/10 text-brand-600">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                        <path d="M18 8.5a6 6 0 0 0-12 0c0 5-2 6.5-2 6.5h16s-2-1.5-2-6.5Z" />
                        <path d="M13.7 19a2 2 0 0 1-3.4 0" />
                    </svg>
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold text-ink-900">${c(n.title)}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-ink-500">${c(n.message)}</p>
                    ${n.url?`<a href="${x(n.url)}" class="mt-2 inline-block text-xs font-semibold text-brand-600 hover:text-brand-700">Lihat detail →</a>`:""}
                </div>
                <button type="button" class="rounded-lg p-1 text-ink-300 transition-colors hover:text-ink-600" aria-label="Tutup notifikasi">✕</button>
            </div>
        `,(r=e.querySelector("button"))==null||r.addEventListener("click",()=>e.remove()),d.append(e),setTimeout(()=>e.remove(),12e3)};setInterval(async()=>{try{const n=await fetch(l,{headers:{Accept:"application/json","X-Requested-With":"XMLHttpRequest"}});if(!n.ok)return;const e=await n.json();o&&(o.textContent=e.unread_count,o.classList.toggle("hidden",!e.unread_count)),p(e.notifications??[]),(e.notifications??[]).filter(r=>!u.has(r.id)).forEach(r=>{u.add(r.id),f(r)})}catch{}},2e4)}function b(){const t=document.querySelector("[data-payment-countdown]");if(!t)return;const s=new Date(t.dataset.deadline??"").getTime();if(!Number.isFinite(s))return;const a=t.dataset.expiredLabel??"Waktu pembayaran habis";let i=!1;const o=()=>{const l=Math.floor((s-Date.now())/1e3);if(l<=0){t.textContent=a,clearInterval(d),i||(i=!0,setTimeout(()=>window.location.reload(),1500));return}const u=Math.floor(l/3600),p=Math.floor(l%3600/60),f=l%60;t.textContent=`${u} Jam ${p} Menit ${f} Detik`},d=setInterval(o,1e3);o()}function h(){document.querySelectorAll("[data-copy]").forEach(t=>{t.addEventListener("click",async()=>{const s=t.textContent;try{await navigator.clipboard.writeText(t.dataset.copy??""),t.textContent=t.dataset.copyDone??"Tersalin!"}catch{t.textContent="Salin manual"}setTimeout(()=>{t.textContent=s},2e3)})})}function c(t){return String(t??"").replace(/&/g,"&amp;").replace(/</g,"&lt;").replace(/>/g,"&gt;")}function x(t){return c(t).replace(/"/g,"&quot;")}
