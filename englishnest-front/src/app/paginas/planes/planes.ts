import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PlanService } from '../../servicios/plan';

@Component({
  selector: 'app-planes',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './planes.html',
  styleUrl: './planes.css'
})
export class Planes implements OnInit {

  planes: any[] = [];
  cargando = true;
  error = '';

  planSeleccionado: any = null;
  mostrarPago = false;
  metodoPago = '';

  planAdquiridoId: number | null = null;

  constructor(private planService: PlanService) {}

  ngOnInit(): void {
    const token = localStorage.getItem('token');

    if (!token) {
      this.error = 'No hay token. Primero inicia sesión.';
      this.cargando = false;
      return;
    }

    this.cargarPlanes(token);
    this.cargarMiSuscripcion(token);
  }

  cargarPlanes(token: string): void {
    this.planService.getPlanes(token).subscribe({
      next: (data) => {
        this.planes = data;
        this.cargando = false;
        console.log('Planes:', data);
      },
      error: (err) => {
        console.error(err);
        this.error = 'No se pudieron cargar los planes.';
        this.cargando = false;
      }
    });
  }

  cargarMiSuscripcion(token: string): void {
    this.planService.miSuscripcion(token).subscribe({
      next: (data) => {
        if (data && data.plan_id) {
          this.planAdquiridoId = data.plan_id;
        }
      },
      error: (err) => {
        console.log('No hay suscripción activa todavía.', err);
      }
    });
  }

  elegirPlan(plan: any): void {
    if (this.planAdquiridoId === plan.id) {
      alert('Ya adquiriste este plan.');
      return;
    }

    if (this.planAdquiridoId !== null) {
      alert('Ya tienes un plan activo. No puedes adquirir otro.');
      return;
    }

    this.planSeleccionado = plan;
    this.mostrarPago = true;
    this.metodoPago = '';
  }

  seleccionarMetodo(metodo: string): void {
    this.metodoPago = metodo;
  }

  confirmarPago(): void {
    const token = localStorage.getItem('token');

    if (!token) {
      alert('No hay token. Inicia sesión.');
      return;
    }

    if (!this.metodoPago) {
      alert('Selecciona un método de pago.');
      return;
    }

    if (!this.planSeleccionado) {
      alert('No seleccionaste ningún plan.');
      return;
    }

    this.planService.comprarPlan(
      token,
      this.planSeleccionado.id,
      this.metodoPago
    ).subscribe({
      next: (res) => {
        this.planAdquiridoId = this.planSeleccionado.id;

        alert(`✅ ${res.message}`);

        this.cerrarModal();
      },
      error: (err) => {
        console.error(err);

        if (err.status === 409) {
          alert('Ya tienes una suscripción activa.');
        } else {
          alert('Error al comprar plan.');
        }
      }
    });
  }

  cerrarModal(): void {
    this.mostrarPago = false;
    this.metodoPago = '';
    this.planSeleccionado = null;
  }

}
