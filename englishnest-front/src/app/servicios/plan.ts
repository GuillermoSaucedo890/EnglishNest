import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class PlanService {

  private apiUrl = 'http://127.0.0.1:8000/api';

  constructor(private http: HttpClient) {}

  private headers(token: string) {
    return new HttpHeaders({
      Accept: 'application/json',
      Authorization: `Bearer ${token}`
    });
  }

  getPlanes(token: string) {
    return this.http.get<any[]>(`${this.apiUrl}/planes`, {
      headers: this.headers(token)
    });
  }

  comprarPlan(token: string, planId: number, metodoPago: string) {
    return this.http.post<any>(
      `${this.apiUrl}/suscripciones`,
      {
        plan_id: planId,
        metodo_pago: metodoPago
      },
      {
        headers: this.headers(token)
      }
    );
  }

  miSuscripcion(token: string) {
    return this.http.get<any>(`${this.apiUrl}/mi-suscripcion`, {
      headers: this.headers(token)
    });
  }
}
