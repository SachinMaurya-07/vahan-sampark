import express from 'express';
import http from 'http';
import { Server } from 'socket.io';

const app=express();
const server=http.createServer(app);
const io=new Server(server,{cors:{origin:'*',methods:['GET','POST']}});

const operators=new Map(); // carCode -> socket
const calls=new Map();     // roomId -> {visitor,operator,carCode}

app.get('/health',(req,res)=>res.json({ok:true}));

io.on('connection',socket=>{
  socket.on('operator:join',({carCode})=>{
    operators.set(carCode,socket.id);
    socket.data.role='operator'; socket.data.carCode=carCode;
  });

  socket.on('visitor:join',({carCode,roomId})=>{
    const op=operators.get(carCode);
    if(!op){socket.emit('call:rejected');return;}
    calls.set(roomId,{visitor:socket.id,operator:op,carCode});
    socket.data.role='visitor'; socket.data.roomId=roomId; socket.data.carCode=carCode;
    io.to(op).emit('visitor:calling',{roomId});
    io.to(socket.id).emit('operator:ready');
  });

  socket.on('call:accepted',({roomId})=>{
    const c=calls.get(roomId); if(!c)return;
    io.to(c.visitor).emit('call:accepted');
  });

  socket.on('call:rejected',({roomId})=>{
    const c=calls.get(roomId); if(!c)return;
    io.to(c.visitor).emit('call:rejected'); calls.delete(roomId);
  });

  socket.on('webrtc:offer',({roomId,offer})=>{
    const c=calls.get(roomId); if(c)io.to(c.operator).emit('webrtc:offer',{offer});
  });

  socket.on('webrtc:answer',({roomId,answer})=>{
    const c=calls.get(roomId); if(c)io.to(c.visitor).emit('webrtc:answer',{answer});
  });

  socket.on('webrtc:ice',({roomId,candidate})=>{
    const c=calls.get(roomId); if(!c)return;
    const target=socket.id===c.visitor?c.operator:c.visitor;
    io.to(target).emit('webrtc:ice',{candidate});
  });

  socket.on('call:end',({roomId})=>{
    const c=calls.get(roomId); if(!c)return;
    const target=socket.id===c.visitor?c.operator:c.visitor;
    io.to(target).emit('call:ended');
    calls.delete(roomId);
  });

  socket.on('disconnect',()=>{
    if(socket.data.role==='operator' && operators.get(socket.data.carCode)===socket.id) operators.delete(socket.data.carCode);
    if(socket.data.roomId){
      const c=calls.get(socket.data.roomId);
      if(c){
        const target=socket.id===c.visitor?c.operator:c.visitor;
        io.to(target).emit('call:ended'); calls.delete(socket.data.roomId);
      }
    }
  });
});

const PORT=process.env.PORT||3000;
server.listen(PORT,()=>console.log(`Signaling server listening on ${PORT}`));
