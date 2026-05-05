import { Routes } from '@angular/router';
import { Cursos } from './paginas/cursos/cursos';
import { Login } from './paginas/login/login';
import { Panel } from './paginas/panel/panel';
import { AdminDocentes } from './paginas/admin-docentes/admin-docentes';
import { EditarCurso } from './paginas/editar-curso/editar-curso';
import { VerCurso } from './paginas/ver-curso/ver-curso';
import { RegistroDocente } from './paginas/registro-docente/registro-docente';
import { RegistroEstudiante } from './paginas/registro-estudiante/registro-estudiante';
import { RestablecerPassword } from './paginas/restablecer-password/restablecer-password';
import { VerificarCorreo } from './paginas/verificar-correo/verificar-correo';
import { Ia } from './paginas/ia/ia';

export const routes: Routes = [
  {
    path: '',
    redirectTo: 'cursos',
    pathMatch: 'full',
  },
  {
    path: 'cursos',
    component: Cursos,
  },
  {
    path: 'curso/:id',
    component: VerCurso,
  },
  {
    path: 'login',
    component: Login,
  },
  {
    path: 'panel',
    component: Panel,
  },
  {
    path: 'admin-docentes',
    component: AdminDocentes,
  },
  {
    path: 'editar-curso/:id',
    component: EditarCurso,
  },
  {
    path: 'registro-docente',
    component: RegistroDocente,
  },
  {
    path: 'registro-estudiante',
    component: RegistroEstudiante,
  },
  {
    path: 'restablecer-password',
    component: RestablecerPassword,
  },
  {
    path: 'verificar-correo',
    component: VerificarCorreo,
  },
  {
    path: 'ia',
    component: Ia,
  },
  {
    path: '**',
    redirectTo: 'cursos',
  },
];