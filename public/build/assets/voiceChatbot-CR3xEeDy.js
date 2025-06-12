import{e as D}from"./livewire.esm-C819BCfU.js";import{R as L}from"./recorder-Cl4mhFdd.js";function _(){return _=Object.assign?Object.assign.bind():function(c){for(var e=1;e<arguments.length;e++){var a=arguments[e];for(var s in a)({}).hasOwnProperty.call(a,s)&&(c[s]=a[s])}return c},_.apply(null,arguments)}function R(c){const e=new Uint8Array(c);return window.btoa(String.fromCharCode(...e))}function q(c){const e=window.atob(c),a=e.length,s=new Uint8Array(a);for(let o=0;o<a;o++)s[o]=e.charCodeAt(o);return s.buffer}const k=new Map;function E(c,e){return async a=>{const s=k.get(c);if(s)return a.addModule(s);const o=new Blob([e],{type:"application/javascript"}),i=URL.createObjectURL(o);try{return await a.addModule(i),void k.set(c,i)}catch{URL.revokeObjectURL(i)}try{const n=`data:application/javascript;base64,${btoa(e)}`;await a.addModule(n),k.set(c,n)}catch{throw new Error(`Failed to load the ${c} worklet module. Make sure the browser supports AudioWorklets.`)}}}const T=E("raw-audio-processor",`
const BIAS = 0x84;
const CLIP = 32635;
const encodeTable = [
  0,0,1,1,2,2,2,2,3,3,3,3,3,3,3,3,
  4,4,4,4,4,4,4,4,4,4,4,4,4,4,4,4,
  5,5,5,5,5,5,5,5,5,5,5,5,5,5,5,5,
  5,5,5,5,5,5,5,5,5,5,5,5,5,5,5,5,
  6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,
  6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,
  6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,
  6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,6,
  7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,
  7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,
  7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,
  7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,
  7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,
  7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,
  7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,
  7,7,7,7,7,7,7,7,7,7,7,7,7,7,7,7
];

function encodeSample(sample) {
  let sign;
  let exponent;
  let mantissa;
  let muLawSample;
  sign = (sample >> 8) & 0x80;
  if (sign !== 0) sample = -sample;
  sample = sample + BIAS;
  if (sample > CLIP) sample = CLIP;
  exponent = encodeTable[(sample>>7) & 0xFF];
  mantissa = (sample >> (exponent+3)) & 0x0F;
  muLawSample = ~(sign | (exponent << 4) | mantissa);
  
  return muLawSample;
}

class RawAudioProcessor extends AudioWorkletProcessor {
  constructor() {
    super();
              
    this.port.onmessage = ({ data }) => {
      switch (data.type) {
        case "setFormat":
          this.isMuted = false;
          this.buffer = []; // Initialize an empty buffer
          this.bufferSize = data.sampleRate / 4;
          this.format = data.format;

          if (globalThis.LibSampleRate && sampleRate !== data.sampleRate) {
            globalThis.LibSampleRate.create(1, sampleRate, data.sampleRate).then(resampler => {
              this.resampler = resampler;
            });
          }
          break;
        case "setMuted":
          this.isMuted = data.isMuted;
          break;
      }
    };
  }
  process(inputs) {
    if (!this.buffer) {
      return true;
    }
    
    const input = inputs[0]; // Get the first input node
    if (input.length > 0) {
      let channelData = input[0]; // Get the first channel's data

      // Resample the audio if necessary
      if (this.resampler) {
        channelData = this.resampler.full(channelData);
      }

      // Add channel data to the buffer
      this.buffer.push(...channelData);
      // Get max volume 
      let sum = 0.0;
      for (let i = 0; i < channelData.length; i++) {
        sum += channelData[i] * channelData[i];
      }
      const maxVolume = Math.sqrt(sum / channelData.length);
      // Check if buffer size has reached or exceeded the threshold
      if (this.buffer.length >= this.bufferSize) {
        const float32Array = this.isMuted 
          ? new Float32Array(this.buffer.length)
          : new Float32Array(this.buffer);

        let encodedArray = this.format === "ulaw"
          ? new Uint8Array(float32Array.length)
          : new Int16Array(float32Array.length);

        // Iterate through the Float32Array and convert each sample to PCM16
        for (let i = 0; i < float32Array.length; i++) {
          // Clamp the value to the range [-1, 1]
          let sample = Math.max(-1, Math.min(1, float32Array[i]));

          // Scale the sample to the range [-32768, 32767]
          let value = sample < 0 ? sample * 32768 : sample * 32767;
          if (this.format === "ulaw") {
            value = encodeSample(Math.round(value));
          }

          encodedArray[i] = value;
        }

        // Send the buffered data to the main script
        this.port.postMessage([encodedArray, maxVolume]);

        // Clear the buffer after sending
        this.buffer = [];
      }
    }
    return true; // Continue processing
  }
}
registerProcessor("raw-audio-processor", RawAudioProcessor);
`);function B(){return["iPad Simulator","iPhone Simulator","iPod Simulator","iPad","iPhone","iPod"].includes(navigator.platform)||navigator.userAgent.includes("Mac")&&"ontouchend"in document}class C{static async create({sampleRate:e,format:a,preferHeadphonesForIosDevices:s}){let o=null,i=null;try{const r={sampleRate:{ideal:e},echoCancellation:{ideal:!0},noiseSuppression:{ideal:!0}};if(B()&&s){const g=(await window.navigator.mediaDevices.enumerateDevices()).find(y=>y.kind==="audioinput"&&["airpod","headphone","earphone"].find(w=>y.label.toLowerCase().includes(w)));g&&(r.deviceId={ideal:g.deviceId})}const l=navigator.mediaDevices.getSupportedConstraints().sampleRate;o=new window.AudioContext(l?{sampleRate:e}:{});const u=o.createAnalyser();l||await o.audioWorklet.addModule("https://cdn.jsdelivr.net/npm/@alexanderolsen/libsamplerate-js@2.1.2/dist/libsamplerate.worklet.js"),await T(o.audioWorklet),i=await navigator.mediaDevices.getUserMedia({audio:r});const v=o.createMediaStreamSource(i),f=new AudioWorkletNode(o,"raw-audio-processor");return f.port.postMessage({type:"setFormat",format:a,sampleRate:e}),v.connect(u),u.connect(f),await o.resume(),new C(o,u,f,i)}catch(r){var n,t;throw(n=i)==null||n.getTracks().forEach(l=>l.stop()),(t=o)==null||t.close(),r}}constructor(e,a,s,o){this.context=void 0,this.analyser=void 0,this.worklet=void 0,this.inputStream=void 0,this.context=e,this.analyser=a,this.worklet=s,this.inputStream=o}async close(){this.inputStream.getTracks().forEach(e=>e.stop()),await this.context.close()}setMuted(e){this.worklet.port.postMessage({type:"setMuted",isMuted:e})}}const P=E("audio-concat-processor",`
const decodeTable = [0,132,396,924,1980,4092,8316,16764];

export function decodeSample(muLawSample) {
  let sign;
  let exponent;
  let mantissa;
  let sample;
  muLawSample = ~muLawSample;
  sign = (muLawSample & 0x80);
  exponent = (muLawSample >> 4) & 0x07;
  mantissa = muLawSample & 0x0F;
  sample = decodeTable[exponent] + (mantissa << (exponent+3));
  if (sign !== 0) sample = -sample;

  return sample;
}

class AudioConcatProcessor extends AudioWorkletProcessor {
  constructor() {
    super();
    this.buffers = []; // Initialize an empty buffer
    this.cursor = 0;
    this.currentBuffer = null;
    this.wasInterrupted = false;
    this.finished = false;
    
    this.port.onmessage = ({ data }) => {
      switch (data.type) {
        case "setFormat":
          this.format = data.format;
          break;
        case "buffer":
          this.wasInterrupted = false;
          this.buffers.push(
            this.format === "ulaw"
              ? new Uint8Array(data.buffer)
              : new Int16Array(data.buffer)
          );
          break;
        case "interrupt":
          this.wasInterrupted = true;
          break;
        case "clearInterrupted":
          if (this.wasInterrupted) {
            this.wasInterrupted = false;
            this.buffers = [];
            this.currentBuffer = null;
          }
      }
    };
  }
  process(_, outputs) {
    let finished = false;
    const output = outputs[0][0];
    for (let i = 0; i < output.length; i++) {
      if (!this.currentBuffer) {
        if (this.buffers.length === 0) {
          finished = true;
          break;
        }
        this.currentBuffer = this.buffers.shift();
        this.cursor = 0;
      }

      let value = this.currentBuffer[this.cursor];
      if (this.format === "ulaw") {
        value = decodeSample(value);
      }
      output[i] = value / 32768;
      this.cursor++;

      if (this.cursor >= this.currentBuffer.length) {
        this.currentBuffer = null;
      }
    }

    if (this.finished !== finished) {
      this.finished = finished;
      this.port.postMessage({ type: "process", finished });
    }

    return true; // Continue processing
  }
}

registerProcessor("audio-concat-processor", AudioConcatProcessor);
`);class S{static async create({sampleRate:e,format:a}){let s=null;try{s=new AudioContext({sampleRate:e});const i=s.createAnalyser(),n=s.createGain();n.connect(i),i.connect(s.destination),await P(s.audioWorklet);const t=new AudioWorkletNode(s,"audio-concat-processor");return t.port.postMessage({type:"setFormat",format:a}),t.connect(n),await s.resume(),new S(s,i,n,t)}catch(i){var o;throw(o=s)==null||o.close(),i}}constructor(e,a,s,o){this.context=void 0,this.analyser=void 0,this.gain=void 0,this.worklet=void 0,this.context=e,this.analyser=a,this.gain=s,this.worklet=o}async close(){await this.context.close()}}function F(c){return!!c.type}class M{static async create(e){let a=null;try{var s;const i=(s=e.origin)!=null?s:"wss://api.elevenlabs.io",n=e.signedUrl?e.signedUrl:i+"/v1/convai/conversation?agent_id="+e.agentId,t=["convai"];e.authorization&&t.push(`bearer.${e.authorization}`),a=new WebSocket(n,t);const r=await new Promise((y,w)=>{a.addEventListener("open",()=>{var p;const h={type:"conversation_initiation_client_data"};var m,d,b,x;e.overrides&&(h.conversation_config_override={agent:{prompt:(m=e.overrides.agent)==null?void 0:m.prompt,first_message:(d=e.overrides.agent)==null?void 0:d.firstMessage,language:(b=e.overrides.agent)==null?void 0:b.language},tts:{voice_id:(x=e.overrides.tts)==null?void 0:x.voiceId}}),e.customLlmExtraBody&&(h.custom_llm_extra_body=e.customLlmExtraBody),e.dynamicVariables&&(h.dynamic_variables=e.dynamicVariables),(p=a)==null||p.send(JSON.stringify(h))},{once:!0}),a.addEventListener("error",p=>{setTimeout(()=>w(p),0)}),a.addEventListener("close",w),a.addEventListener("message",p=>{const h=JSON.parse(p.data);F(h)&&(h.type==="conversation_initiation_metadata"?y(h.conversation_initiation_metadata_event):console.warn("First received message is not conversation metadata."))},{once:!0})}),{conversation_id:l,agent_output_audio_format:u,user_input_audio_format:v}=r,f=A(v??"pcm_16000"),g=A(u);return new M(a,l,f,g)}catch(i){var o;throw(o=a)==null||o.close(),i}}constructor(e,a,s,o){this.socket=void 0,this.conversationId=void 0,this.inputFormat=void 0,this.outputFormat=void 0,this.queue=[],this.disconnectionDetails=null,this.onDisconnectCallback=null,this.onMessageCallback=null,this.socket=e,this.conversationId=a,this.inputFormat=s,this.outputFormat=o,this.socket.addEventListener("error",i=>{setTimeout(()=>this.disconnect({reason:"error",message:"The connection was closed due to a socket error.",context:i}),0)}),this.socket.addEventListener("close",i=>{this.disconnect(i.code===1e3?{reason:"agent",context:i}:{reason:"error",message:i.reason||"The connection was closed by the server.",context:i})}),this.socket.addEventListener("message",i=>{try{const n=JSON.parse(i.data);if(!F(n))return;this.onMessageCallback?this.onMessageCallback(n):this.queue.push(n)}catch{}})}close(){this.socket.close()}sendMessage(e){this.socket.send(JSON.stringify(e))}onMessage(e){this.onMessageCallback=e,this.queue.forEach(e),this.queue=[]}onDisconnect(e){this.onDisconnectCallback=e,this.disconnectionDetails&&e(this.disconnectionDetails)}disconnect(e){var a;this.disconnectionDetails||(this.disconnectionDetails=e,(a=this.onDisconnectCallback)==null||a.call(this,e))}}function A(c){const[e,a]=c.split("_");if(!["pcm","ulaw"].includes(e))throw new Error(`Invalid format: ${c}`);const s=parseInt(a);if(isNaN(s))throw new Error(`Invalid sample rate: ${a}`);return{format:e,sampleRate:s}}const V={clientTools:{}},O={onConnect:()=>{},onDebug:()=>{},onDisconnect:()=>{},onError:()=>{},onMessage:()=>{},onAudio:()=>{},onModeChange:()=>{},onStatusChange:()=>{},onCanSendFeedbackChange:()=>{}};class I{static async startSession(e){var a;const s=_({},V,O,e);s.onStatusChange({status:"connecting"}),s.onCanSendFeedbackChange({canSendFeedback:!1});let o=null,i=null,n=null,t=null,r=null;if((a=e.useWakeLock)==null||a)try{r=await navigator.wakeLock.request("screen")}catch{}try{var l,u;t=await navigator.mediaDevices.getUserMedia({audio:!0});const m=(l=e.connectionDelay)!=null?l:{default:0,android:3e3};let d=m.default;var v;if(/android/i.test(navigator.userAgent))d=(v=m.android)!=null?v:d;else if(B()){var f;d=(f=m.ios)!=null?f:d}return d>0&&await new Promise(b=>setTimeout(b,d)),i=await M.create(e),[o,n]=await Promise.all([C.create(_({},i.inputFormat,{preferHeadphonesForIosDevices:e.preferHeadphonesForIosDevices})),S.create(i.outputFormat)]),(u=t)==null||u.getTracks().forEach(b=>b.stop()),t=null,new I(s,i,o,n,r)}catch(m){var g,y,w,p;s.onStatusChange({status:"disconnected"}),(g=t)==null||g.getTracks().forEach(d=>d.stop()),(y=i)==null||y.close(),await((w=o)==null?void 0:w.close()),await((p=n)==null?void 0:p.close());try{var h;await((h=r)==null?void 0:h.release()),r=null}catch{}throw m}}constructor(e,a,s,o,i){var n=this;this.options=void 0,this.connection=void 0,this.input=void 0,this.output=void 0,this.wakeLock=void 0,this.lastInterruptTimestamp=0,this.mode="listening",this.status="connecting",this.inputFrequencyData=void 0,this.outputFrequencyData=void 0,this.volume=1,this.currentEventId=1,this.lastFeedbackEventId=1,this.canSendFeedback=!1,this.endSession=()=>this.endSessionWithDetails({reason:"user"}),this.endSessionWithDetails=async function(t){if(n.status==="connected"||n.status==="connecting"){n.updateStatus("disconnecting");try{var r;await((r=n.wakeLock)==null?void 0:r.release()),n.wakeLock=null}catch{}n.connection.close(),await n.input.close(),await n.output.close(),n.updateStatus("disconnected"),n.options.onDisconnect(t)}},this.updateMode=t=>{t!==this.mode&&(this.mode=t,this.options.onModeChange({mode:t}))},this.updateStatus=t=>{t!==this.status&&(this.status=t,this.options.onStatusChange({status:t}))},this.updateCanSendFeedback=()=>{const t=this.currentEventId!==this.lastFeedbackEventId;this.canSendFeedback!==t&&(this.canSendFeedback=t,this.options.onCanSendFeedbackChange({canSendFeedback:t}))},this.onMessage=async function(t){switch(t.type){case"interruption":return t.interruption_event&&(n.lastInterruptTimestamp=t.interruption_event.event_id),void n.fadeOutAudio();case"agent_response":return void n.options.onMessage({source:"ai",message:t.agent_response_event.agent_response});case"user_transcript":return void n.options.onMessage({source:"user",message:t.user_transcription_event.user_transcript});case"internal_tentative_agent_response":return void n.options.onDebug({type:"tentative_agent_response",response:t.tentative_agent_response_internal_event.tentative_agent_response});case"client_tool_call":if(n.options.clientTools.hasOwnProperty(t.client_tool_call.tool_name))try{var r;const l=(r=await n.options.clientTools[t.client_tool_call.tool_name](t.client_tool_call.parameters))!=null?r:"Client tool execution successful.",u=typeof l=="object"?JSON.stringify(l):String(l);n.connection.sendMessage({type:"client_tool_result",tool_call_id:t.client_tool_call.tool_call_id,result:u,is_error:!1})}catch(l){n.onError("Client tool execution failed with following error: "+(l==null?void 0:l.message),{clientToolName:t.client_tool_call.tool_name}),n.connection.sendMessage({type:"client_tool_result",tool_call_id:t.client_tool_call.tool_call_id,result:"Client tool execution failed: "+(l==null?void 0:l.message),is_error:!0})}else{if(n.options.onUnhandledClientToolCall)return void n.options.onUnhandledClientToolCall(t.client_tool_call);n.onError(`Client tool with name ${t.client_tool_call.tool_name} is not defined on client`,{clientToolName:t.client_tool_call.tool_name}),n.connection.sendMessage({type:"client_tool_result",tool_call_id:t.client_tool_call.tool_call_id,result:`Client tool with name ${t.client_tool_call.tool_name} is not defined on client`,is_error:!0})}return;case"audio":return void(n.lastInterruptTimestamp<=t.audio_event.event_id&&(n.options.onAudio(t.audio_event.audio_base_64),n.addAudioBase64Chunk(t.audio_event.audio_base_64),n.currentEventId=t.audio_event.event_id,n.updateCanSendFeedback(),n.updateMode("speaking")));case"ping":return void n.connection.sendMessage({type:"pong",event_id:t.ping_event.event_id});default:return void n.options.onDebug(t)}},this.onInputWorkletMessage=t=>{this.status==="connected"&&this.connection.sendMessage({user_audio_chunk:R(t.data[0].buffer)})},this.onOutputWorkletMessage=({data:t})=>{t.type==="process"&&this.updateMode(t.finished?"listening":"speaking")},this.addAudioBase64Chunk=t=>{this.output.gain.gain.value=this.volume,this.output.worklet.port.postMessage({type:"clearInterrupted"}),this.output.worklet.port.postMessage({type:"buffer",buffer:q(t)})},this.fadeOutAudio=()=>{this.updateMode("listening"),this.output.worklet.port.postMessage({type:"interrupt"}),this.output.gain.gain.exponentialRampToValueAtTime(1e-4,this.output.context.currentTime+2),setTimeout(()=>{this.output.gain.gain.value=this.volume,this.output.worklet.port.postMessage({type:"clearInterrupted"})},2e3)},this.onError=(t,r)=>{console.error(t,r),this.options.onError(t,r)},this.calculateVolume=t=>{if(t.length===0)return 0;let r=0;for(let l=0;l<t.length;l++)r+=t[l]/255;return r/=t.length,r<0?0:r>1?1:r},this.getId=()=>this.connection.conversationId,this.isOpen=()=>this.status==="connected",this.setVolume=({volume:t})=>{this.volume=t},this.setMicMuted=t=>{this.input.setMuted(t)},this.getInputByteFrequencyData=()=>(this.inputFrequencyData!=null||(this.inputFrequencyData=new Uint8Array(this.input.analyser.frequencyBinCount)),this.input.analyser.getByteFrequencyData(this.inputFrequencyData),this.inputFrequencyData),this.getOutputByteFrequencyData=()=>(this.outputFrequencyData!=null||(this.outputFrequencyData=new Uint8Array(this.output.analyser.frequencyBinCount)),this.output.analyser.getByteFrequencyData(this.outputFrequencyData),this.outputFrequencyData),this.getInputVolume=()=>this.calculateVolume(this.getInputByteFrequencyData()),this.getOutputVolume=()=>this.calculateVolume(this.getOutputByteFrequencyData()),this.sendFeedback=t=>{this.canSendFeedback?(this.connection.sendMessage({type:"feedback",score:t?"like":"dislike",event_id:this.currentEventId}),this.lastFeedbackEventId=this.currentEventId,this.updateCanSendFeedback()):console.warn(this.lastFeedbackEventId===0?"Cannot send feedback: the conversation has not started yet.":"Cannot send feedback: feedback has already been sent for the current response.")},this.sendContextualUpdate=t=>{this.connection.sendMessage({type:"contextual_update",text:t})},this.options=e,this.connection=a,this.input=s,this.output=o,this.wakeLock=i,this.options.onConnect({conversationId:a.conversationId}),this.connection.onDisconnect(this.endSessionWithDetails),this.connection.onMessage(this.onMessage),this.input.worklet.port.onmessage=this.onInputWorkletMessage,this.output.worklet.port.onmessage=this.onOutputWorkletMessage,this.updateStatus("connected")}}const U=(c,e)=>({agentId:c,uuId:e,bubbleMessage:"Need help?",coversation:null,audioRecorder:null,chatbotStatus:null,startConversationBtn:null,stopConversationBtn:null,audioVisEl:null,init(){this.chatbotStatus=document.getElementById("lqd-ext-chatbot-voice-bot-status"),this.bubbleMessage=this.chatbotStatus.textContent,this.startConversationBtn=document.getElementById("lqd-ext-chatbot-voice-start-btn"),this.stopConversationBtn=document.getElementById("lqd-ext-chatbot-voice-end-btn"),this.audioVisEl=document.getElementById("lqd-ext-chatbot-voice-vis-img"),this.initRecorder(),this.addEventListeners()},addEventListeners(){this.startConversationBtn.addEventListener("click",()=>this.startConversation()),this.stopConversationBtn.addEventListener("click",()=>this.stopConversation())},async startConversation(){try{this.startConversationBtn.setAttribute("disabled",!0),this.startConversationBtn.querySelector("span").textContent="starting...";const a=await navigator.mediaDevices.getUserMedia({audio:!0,video:!1});this.conversation=await I.startSession({agentId:this.agentId,onConnect:async()=>{var s;this.startConversationBtn.style.display="none",this.stopConversationBtn.style.display="flex",await((s=this.audioRecorder)==null?void 0:s.start(a)),this.startDotVisualizer()},onDisconnect:()=>{var s;this.startConversationBtn.style.display="flex",this.stopConversationBtn.style.display="none",this.chatbotStatus.textContent=this.bubbleMessage,this.audioVisEl&&(this.audioVisEl.style.transform="scale(1)",this.audioVisEl.style.opacity=1),this.storeConversation(this.conversation.getId()),(s=this.audioRecorder)==null||s.stop()},onModeChange:s=>{this.chatbotStatus.textContent=s.mode==="speaking"?"speaking":"listening"},onError:s=>{console.error("Error:",s)}}),this.startConversationBtn.setAttribute("disabled",!1),this.startConversationBtn.querySelector("span").textContent="Voice Chat"}catch(a){console.error("Failed to start conversation:",a)}},async stopConversation(){this.conversation&&(await this.conversation.endSession(),this.conversation=null)},async initRecorder(){try{this.audioRecorder=new L(this.handleAudioRecordingBuffer)}catch(a){console.error("Error starting audio recorder:",a)}},async storeConversation(a){const s=await fetch(`/api/v2/chatbot-voice/${this.uuId}/store-conversation`,{method:"POST",headers:{"Content-Type":"application/json",Accept:"application/json"},body:JSON.stringify({conversation_id:a})});try{const o=await s.json();s.ok||console.error("Failed create conversation:",o.message)}catch(o){console.error("Failed parse JSON:",o)}},handleAudioRecordingBuffer(a){},startDotVisualizer(){if(!this.audioRecorder||!this.audioVisEl)return;const a=this.audioRecorder.audioContext.createAnalyser();a.fftSize=256;const s=a.frequencyBinCount,o=new Uint8Array(s);if(this.audioRecorder.getMediaStreamSource().connect(a),!this.audioVisEl)return;const i=()=>{a.getByteFrequencyData(o);let n=0;for(let u=0;u<s;u++)n+=o[u];const r=1+n/s/256*1.2,l=Math.max(.2,1-(r-1)/1.5);this.audioVisEl.style.transform=`scale(${r})`,this.audioVisEl.style.opacity=l.toFixed(2),requestAnimationFrame(i)};i()}});window.Alpine=D;document.addEventListener("alpine:init",()=>{D.data("elevenLabsConversationalAI",U)});
