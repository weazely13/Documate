<div wire:ignore class="mt-2"
     x-data="{
        drawing:false, has:false, ctx:null,
        init(){ const c=this.$refs.c; this.ctx=c.getContext('2d'); this.ctx.lineWidth=4; this.ctx.lineCap='round'; this.ctx.lineJoin='round'; this.ctx.strokeStyle='#111'; },
        pos(e){ const c=this.$refs.c,r=c.getBoundingClientRect(); return [(e.clientX-r.left)*c.width/r.width,(e.clientY-r.top)*c.height/r.height]; },
        start(e){ this.drawing=true; const [x,y]=this.pos(e); this.ctx.beginPath(); this.ctx.moveTo(x,y); this.ctx.lineTo(x+.1,y+.1); this.ctx.stroke(); },
        move(e){ if(!this.drawing)return; const [x,y]=this.pos(e); this.ctx.lineTo(x,y); this.ctx.stroke(); this.has=true; },
        end(){ if(!this.drawing)return; this.drawing=false; if(this.has){ $wire.$set('{{ $model }}', this.$refs.c.toDataURL('image/png'), false); } },
        clear(){ this.ctx.clearRect(0,0,600,200); this.has=false; $wire.$set('{{ $model }}','',false); }
     }">
    <canvas x-ref="c" width="600" height="200" class="w-full touch-none rounded-xl border border-dashed border-slate-300 bg-white"
            @pointerdown.prevent="start($event)" @pointermove.prevent="move($event)" @pointerup="end()" @pointerleave="end()"></canvas>
    <button type="button" @click="clear()" class="mt-1.5 text-xs font-medium text-slate-500 hover:text-rose-600">Clear</button>
</div>