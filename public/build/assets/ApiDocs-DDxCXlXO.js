import{_ as w}from"./AppLayout-DgG7pByb.js";import{c as v,w as b,a as e,o as n,d as i,g as x,t as a,f as d,F as g,r as h,n as l,h as _}from"./app-awVTsjzM.js";import"./_plugin-vue_export-helper-DlAUqK2U.js";const y={class:"py-12"},k={class:"max-w-7xl mx-auto sm:px-6 lg:px-8"},C={key:0,class:"bg-gradient-to-br from-red-50 to-orange-50 border border-red-100 rounded-[2.5rem] p-10 text-center mb-12 shadow-xl shadow-red-100/50"},T={class:"bg-gradient-to-br from-[#780116] via-[#c32f27] to-[#780116] rounded-[3rem] p-12 mb-12 text-white relative overflow-hidden shadow-2xl shadow-red-900/20"},I={class:"relative z-10 flex flex-col md:flex-row items-center justify-between gap-10 text-center md:text-left"},A={class:"flex-grow"},B={class:"flex flex-wrap items-center justify-center md:justify-start gap-6"},G={class:"bg-black/20 backdrop-blur-md border border-white/10 rounded-2xl px-6 py-4 flex items-center gap-4"},P={class:"text-[#f7b538] font-mono font-bold text-sm"},Z={class:"grid grid-cols-1 lg:grid-cols-3 gap-8"},M={class:"lg:col-span-1"},S={class:"bg-white rounded-2xl shadow-xl p-4 sticky top-4"},j={class:"space-y-1"},z=["onClick"],E={class:"font-black text-[11px] uppercase tracking-widest"},q={class:"lg:col-span-2"},D={class:"bg-white rounded-2xl shadow-xl overflow-hidden"},N={class:"p-10 border-b border-slate-50 bg-white"},H={class:"flex items-center gap-4 mb-6"},L={class:"text-[#db7c26] font-mono font-bold bg-orange-50 px-4 py-1.5 rounded-xl border border-orange-100 text-sm"},V={class:"text-3xl font-black text-slate-900 mb-3 tracking-tight"},W={class:"text-slate-500 font-bold leading-relaxed"},Y={key:0,class:"p-10 border-b border-slate-50"},U={class:"overflow-x-auto"},J={class:"w-full text-sm"},R={class:"py-5"},O={class:"text-[#780116] font-mono font-bold"},F={class:"py-5 font-bold text-slate-500"},$={class:"py-5"},X={class:"py-5 text-slate-500 font-bold max-w-xs"},Q={class:"p-10 bg-slate-50/20"},K={class:"flex items-center justify-between mb-6"},ee={class:"bg-slate-900 rounded-[2rem] p-8 font-mono text-sm overflow-x-auto shadow-2xl shadow-red-950/10 border-t-4 border-[#780116]"},te={class:"text-[#f7b538] whitespace-pre-wrap leading-relaxed"},ne={__name:"ApiDocs",props:{hasApiAccess:Boolean,baseUrl:String},setup(p){const c=_("profile"),u=[{id:"profile",name:"Get Profile",method:"GET",path:"/api/v1/profile",description:"Get your user profile and subscription information.",params:[],response:`{
  "success": true,
  "data": {
    "id": 7,
    "name": "Your Name",
    "email": "user@example.com",
    "subscription": {
      "plan": "Business",
      "status": "active",
      "limits": {
        "whatsapp_nos": 5,
        "contacts": 10000,
        "messages": 20000,
        "features": {
          "api_access": true,
          "auto_reply": true,
          "webhooks": true,
          "scheduling": true,
          "multi_user": true,
          "pdf_support": true,
          "file_support": true,
          "image_support": true,
          "text_support": true,
          "link_preview": true,
          "message_preview": true
        }
      },
      "messages_used": 0
    }
  }
}`},{id:"usage",name:"Get Usage Stats",method:"GET",path:"/api/v1/usage",description:"Get your current usage statistics and remaining limits.",params:[],response:`{
  "success": true,
  "data": {
    "devices": { "used": 1, "limit": 5 },
    "contacts": { "used": 1, "limit": 10000 },
    "messages": { "used": 0, "limit": 20000 },
    "features": {
      "api_access": true,
      "webhooks": true,
      "auto_reply": true,
      "multi_user": true,
      "scheduling": true,
      "pdf_support": true,
      "file_support": true,
      "image_support": true,
      "text_support": true,
      "link_preview": true,
      "message_preview": true
    }
  }
}`},{id:"contacts-list",name:"List Contacts",method:"GET",path:"/api/v1/contacts",description:"Get a paginated list of all your contacts.",params:[{name:"search",type:"string",required:!1,description:"Search by name or phone"},{name:"per_page",type:"integer",required:!1,description:"Items per page (default: 50)"},{name:"page",type:"integer",required:!1,description:"Page number"}],response:`{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 9,
        "user_id": 7,
        "whatsapp_number_id": null,
        "name": "John Doe",
        "phone_number": "60123456789",
        "country_code": "60",
        "created_at": "2026-02-04T17:49:55.000000Z",
        "updated_at": "2026-02-04T17:49:55.000000Z"
      }
    ],
    "total": 1,
    "per_page": 50
  }
}`},{id:"contacts-create",name:"Create Contact",method:"POST",path:"/api/v1/contacts",description:"Create a new contact in your database.",params:[{name:"name",type:"string",required:!0,description:"Contact name"},{name:"phone",type:"string",required:!0,description:"Phone number with country code (e.g., 60123456789)"},{name:"country_code",type:"string",required:!1,description:"Country code (default: 60 for Malaysia)"}],response:`{
  "success": true,
  "data": {
    "id": 10,
    "user_id": 7,
    "name": "Jane Smith",
    "phone_number": "60198765432",
    "country_code": "60",
    "created_at": "2026-02-06T02:55:00.000000Z",
    "updated_at": "2026-02-06T02:55:00.000000Z"
  },
  "message": "Contact created successfully."
}`},{id:"devices",name:"List Devices",method:"GET",path:"/api/v1/devices",description:"Get all your connected WhatsApp devices and their status.",params:[],response:`{
  "success": true,
  "data": [
    {
      "id": 30,
      "user_id": 7,
      "phone_number": "60148885659:42@s.whatsapp.net",
      "status": "connected",
      "phone_info": {
        "id": "60148885659:42@s.whatsapp.net",
        "lid": "164940266635464:42@lid",
        "name": "Your Business Name"
      },
      "created_at": "2026-02-05T17:20:08.000000Z",
      "updated_at": "2026-02-05T18:08:40.000000Z"
    }
  ]
}`},{id:"send-message",name:"Send Message",method:"POST",path:"/api/v1/messages/send",description:"Send a WhatsApp message to a phone number.",params:[{name:"phone",type:"string",required:!0,description:"Recipient phone number with country code"},{name:"message",type:"string",required:!0,description:"Message content (max 4096 chars)"},{name:"device_id",type:"integer",required:!1,description:"Specific device ID to use (optional)"}],response:`{
  "success": true,
  "message": "Message sent successfully.",
  "data": {
    "phone": "60123456789",
    "device_id": 1
  }
}`},{id:"campaigns",name:"List Campaigns",method:"GET",path:"/api/v1/campaigns",description:"Get all your campaigns with pagination.",params:[{name:"per_page",type:"integer",required:!1,description:"Items per page (default: 20)"},{name:"page",type:"integer",required:!1,description:"Page number"}],response:`{
  "success": true,
  "data": {
    "current_page": 1,
    "data": [
      {
        "id": 44,
        "name": "January Promotion",
        "user_id": 7,
        "whatsapp_number_id": 30,
        "message_type": "text",
        "body": "Your campaign message",
        "media_path": null,
        "scheduled_at": "2026-02-05T17:23:00.000000Z",
        "status": "completed",
        "is_drip": false,
        "drip_delay_minutes": null,
        "created_at": "2026-02-05T17:22:40.000000Z",
        "updated_at": "2026-02-05T17:23:07.000000Z",
        "whatsapp_number": {
          "id": 30,
          "phone_number": "60148885659:42@s.whatsapp.net",
          "status": "connected",
          "phone_info": {
            "name": "Your Business"
          }
        }
      }
    ],
    "total": 2,
    "per_page": 5
  }
}`}],f=o=>{navigator.clipboard.writeText(o)},m=o=>{switch(o){case"GET":return"bg-orange-50 text-[#db7c26] border-orange-100";case"POST":return"bg-red-50 text-[#780116] border-red-100";case"PUT":return"bg-amber-50 text-[#f7b538] border-amber-100";case"DELETE":return"bg-rose-50 text-rose-600 border-rose-100";default:return"bg-slate-50 text-slate-500 border-slate-100"}},r=()=>u.find(o=>o.id===c.value);return(o,t)=>(n(),v(w,{title:"API Documentation"},{header:b(()=>[...t[1]||(t[1]=[e("h2",{class:"font-black text-2xl text-slate-800 leading-tight tracking-tight"}," Developer Interface ",-1)])]),default:b(()=>[e("div",y,[e("div",k,[p.hasApiAccess?x("",!0):(n(),i("div",C,[...t[2]||(t[2]=[e("div",{class:"w-20 h-20 bg-white rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-lg"},[e("svg",{class:"w-10 h-10 text-[#780116]",fill:"none",stroke:"currentColor",viewBox:"0 0 24 24"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2.5",d:"M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"})])],-1),e("h3",{class:"text-2xl font-black text-slate-900 mb-2 tracking-tight"},"API Interface Locked",-1),e("p",{class:"text-slate-500 font-bold text-xs uppercase tracking-widest mb-8"},"Synchronized API access is exclusive to Business Tier members",-1),e("a",{href:"/subscription",class:"inline-flex items-center px-10 py-5 bg-[#780116] text-white rounded-2xl font-black text-[11px] uppercase tracking-[0.2em] hover:bg-[#c32f27] transition shadow-2xl shadow-red-200 transform active:scale-95"}," Upgrade To Business Tier ",-1)])])),e("div",T,[t[8]||(t[8]=e("div",{class:"absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wMyI+PHBhdGggZD0iTTM2IDM0djItSDI0di0yaDEyek0zNiAzNHYtMkgyNHYyaDEyeiIvPjwvZz48L2c+PC9zdmc+')] opacity-30"},null,-1)),e("div",I,[e("div",A,[t[5]||(t[5]=e("div",{class:"flex items-center justify-center md:justify-start gap-4 mb-6"},[e("div",{class:"bg-white/10 backdrop-blur-md p-3 rounded-2xl border border-white/20"},[e("svg",{class:"w-8 h-8 text-[#f7b538]",fill:"none",stroke:"currentColor",viewBox:"0 0 24 24"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2.5",d:"M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"})])]),e("span",{class:"text-[#f7b538] font-black uppercase tracking-[0.3em] text-xs"},"Sendora REST Cluster")],-1)),t[6]||(t[6]=e("h1",{class:"text-4xl md:text-5xl font-black mb-6 tracking-tighter leading-none"},"Sendora Neural API",-1)),t[7]||(t[7]=e("p",{class:"text-white/80 font-bold text-sm md:text-base max-w-xl leading-relaxed mb-10"}," Seamlessly integrate high-performance WhatsApp transmission into your architecture. Automate global communication flows with our precision-engineered REST interface. ",-1)),e("div",B,[e("div",G,[t[3]||(t[3]=e("span",{class:"text-white/40 font-black text-[10px] uppercase tracking-widest"},"Base Vector:",-1)),e("code",P,a(p.baseUrl)+"/api/v1",1)]),t[4]||(t[4]=e("a",{href:"/user/api-tokens",class:"bg-white text-[#780116] px-8 py-4 rounded-xl font-black text-[11px] uppercase tracking-widest hover:bg-[#f7b538] hover:text-white transition-all shadow-xl shadow-black/20 flex items-center gap-3"},[e("svg",{class:"w-5 h-5 font-black",fill:"none",stroke:"currentColor",viewBox:"0 0 24 24"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2.5",d:"M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"})]),d(" Initialize Tokens ")],-1))])])])]),t[15]||(t[15]=e("div",{class:"bg-white rounded-[2.5rem] shadow-2xl shadow-slate-200/50 p-10 mb-12 border border-slate-50"},[e("h2",{class:"text-2xl font-black text-slate-900 mb-6 flex items-center gap-4"},[e("div",{class:"w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center border border-red-100"},[e("svg",{class:"w-6 h-6 text-[#780116]",fill:"none",stroke:"currentColor",viewBox:"0 0 24 24"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2.5",d:"M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"})])]),d(" Authentication Gateway ")]),e("p",{class:"text-slate-500 font-bold text-sm mb-8 max-w-2xl leading-relaxed"}," Secure your request transmissions using high-bit Bearer authorization. Initialize a secure token from your dashboard and include it in your transmission headers. "),e("div",{class:"bg-slate-900 rounded-[1.5rem] p-6 font-mono text-sm overflow-x-auto shadow-2xl shadow-red-900/10 border-t-2 border-[#780116]"},[e("div",{class:"text-slate-500 mb-3 font-bold uppercase text-[10px] tracking-widest"},"// Authorization Cluster"),e("div",{class:"text-white font-bold tracking-tight"},[d("Authorization: "),e("span",{class:"text-[#f7b538]"},"Bearer"),d(),e("span",{class:"bg-white/10 px-3 py-1 rounded-lg text-white"},"your_secure_protocol_token")])])],-1)),e("div",Z,[e("div",M,[e("div",S,[t[9]||(t[9]=e("h3",{class:"font-bold text-slate-900 mb-4 px-2"},"Endpoints",-1)),e("nav",j,[(n(),i(g,null,h(u,s=>e("button",{key:s.id,onClick:se=>c.value=s.id,class:l(["w-full flex items-center gap-4 px-5 py-4 rounded-2xl text-left transition-all border border-transparent",c.value===s.id?"bg-red-50 text-[#780116] border-red-100 shadow-sm":"hover:bg-slate-50 text-slate-400 font-bold"])},[e("span",{class:l(["w-12 text-center py-1 rounded-lg text-[10px] font-black border uppercase tracking-wider",m(s.method)])},a(s.method),3),e("span",E,a(s.name),1)],10,z)),64))])])]),e("div",q,[e("div",D,[e("div",N,[e("div",H,[e("span",{class:l(["px-5 py-1.5 rounded-xl text-xs font-black border uppercase tracking-[0.2em]",m(r().method)])},a(r().method),3),e("code",L,a(r().path),1)]),e("h2",V,a(r().name),1),e("p",W,a(r().description),1)]),r().params.length>0?(n(),i("div",Y,[t[11]||(t[11]=e("h3",{class:"font-black text-[10px] text-slate-400 uppercase tracking-[0.2em] mb-8"},"Request Parameters",-1)),e("div",U,[e("table",J,[t[10]||(t[10]=e("thead",null,[e("tr",{class:"text-[10px] text-slate-400 uppercase tracking-widest border-b border-slate-50"},[e("th",{class:"text-left font-black pb-4"},"Variable"),e("th",{class:"text-left font-black pb-4"},"Primitive"),e("th",{class:"text-left font-black pb-4"},"Constraint"),e("th",{class:"text-left font-black pb-4"},"Context")])],-1)),e("tbody",null,[(n(!0),i(g,null,h(r().params,s=>(n(),i("tr",{key:s.name,class:"border-b border-slate-50/50 last:border-0 hover:bg-slate-50/30 transition-colors"},[e("td",R,[e("code",O,a(s.name),1)]),e("td",F,a(s.type),1),e("td",$,[e("span",{class:l([s.required?"text-[#780116] bg-red-50 border-red-100":"text-slate-400 bg-slate-50 border-slate-100","px-3 py-1 rounded-lg text-[9px] font-black uppercase tracking-widest border"])},a(s.required?"Critical":"Optional"),3)]),e("td",X,a(s.description),1)]))),128))])])])])):x("",!0),e("div",Q,[e("div",K,[t[13]||(t[13]=e("h3",{class:"font-black text-[10px] text-slate-400 uppercase tracking-[0.2em]"},"Validated Response Buffer",-1)),e("button",{onClick:t[0]||(t[0]=s=>f(r().response)),class:"text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-[#780116] flex items-center gap-2 transition-colors"},[...t[12]||(t[12]=[e("svg",{class:"w-4 h-4",fill:"none",stroke:"currentColor",viewBox:"0 0 24 24"},[e("path",{"stroke-linecap":"round","stroke-linejoin":"round","stroke-width":"2.5",d:"M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"})],-1),d(" Copy Buffer ",-1)])])]),e("div",ee,[e("pre",te,a(r().response),1)])])]),t[14]||(t[14]=e("div",{class:"bg-white rounded-2xl shadow-xl p-6 mt-8"},[e("h3",{class:"font-bold text-slate-900 mb-4"},"Error Codes"),e("div",{class:"space-y-3"},[e("div",{class:"flex items-center gap-3 p-3 bg-red-50 rounded-lg"},[e("span",{class:"bg-red-100 text-red-700 px-2 py-1 rounded font-mono text-sm font-bold"},"401"),e("span",{class:"text-red-700"},"Unauthorized - Invalid or missing API token")]),e("div",{class:"flex items-center gap-3 p-3 bg-amber-50 rounded-lg"},[e("span",{class:"bg-amber-100 text-amber-700 px-2 py-1 rounded font-mono text-sm font-bold"},"403"),e("span",{class:"text-amber-700"},"Forbidden - API access not available on your plan")]),e("div",{class:"flex items-center gap-3 p-3 bg-orange-50 rounded-lg"},[e("span",{class:"bg-orange-100 text-orange-700 px-2 py-1 rounded font-mono text-sm font-bold"},"422"),e("span",{class:"text-orange-700"},"Validation Error - Check your request parameters")]),e("div",{class:"flex items-center gap-3 p-3 bg-slate-100 rounded-lg"},[e("span",{class:"bg-slate-200 text-slate-700 px-2 py-1 rounded font-mono text-sm font-bold"},"500"),e("span",{class:"text-slate-700"},"Server Error - Something went wrong on our end")])])],-1))])])])])]),_:1}))}};export{ne as default};
