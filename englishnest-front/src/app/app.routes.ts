import { Routes } from '@angular/router';
import { Login } from './paginas/login/login';
import { VerificarCorreo } from './paginas/verificar-correo/verificar-correo';
import { Panel } from './paginas/panel/panel';
import { AdminDocentes } from './paginas/admin-docentes/admin-docentes';
import { Cursos } from './paginas/cursos/cursos';
import { Ia } from './paginas/ia/ia';
import { Planes } from './paginas/planes/planes';
import { CursoDetalle } from './paginas/curso-detalle/curso-detalle';
import { MisCursos } from './paginas/mis-cursos/mis-cursos';

export const routes: Routes = [
  { path: '', redirectTo: 'login', pathMatch: 'full' },

  { path: 'login', component: Login },
  { path: 'verificar-correo', component: VerificarCorreo },

  { path: 'panel', component: Panel },
  { path: 'cursos', component: Cursos },
  { path: 'planes', component: Planes }, // 🔥 IMPORTANTE
  { path: 'ia', component: Ia },
  { path: 'admin/docentes', component: AdminDocentes },
  { path: 'curso-detalle/:id', component: CursoDetalle },
  { path: 'mis-cursos', component: MisCursos },
];


